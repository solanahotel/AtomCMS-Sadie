<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HotelTokenService;
use App\Services\SolanaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Credit Exchange on-chain settlement (Phase 5). Mirrors the club-payment intent->verify flow, but
// for an SPL ($HOTEL) split: buyer pays the seller wallet (amount - fee) and the treasury (fee) in one
// tx; only after on-chain finalization do we release the escrowed credit-item to the buyer.
//
// Entirely INERT until config('solana.hotel.exchange_enabled') is true AND the mint/wallets are set —
// every endpoint returns `exchange_not_enabled` otherwise, so nothing settles early.
class CreditExchangeController extends Controller
{
    public function __construct(
        private readonly HotelTokenService $hotel,
        private readonly SolanaPaymentService $solana,
    ) {
    }

    private function assertInternal(Request $request): void
    {
        $secret = (string) config('solana.internal_secret');
        if ($secret === '' || !hash_equals($secret, (string) $request->header('X-Internal-Secret', ''))) {
            abort(403, 'forbidden');
        }
    }

    private function guardEnabled(): ?JsonResponse
    {
        return $this->hotel->isExchangeLive() ? null
            : response()->json(['error' => 'exchange_not_enabled'], 503);
    }

    // POST /api/internal/hotel/intent { player_id (buyer), listing_id }
    public function intent(Request $request): JsonResponse
    {
        $this->assertInternal($request);
        if ($blocked = $this->guardEnabled()) return $blocked;

        $data = $request->validate(['player_id' => 'required|integer', 'listing_id' => 'required|integer']);

        $listing = DB::table('marketplace_listings')
            ->where('id', $data['listing_id'])->where('section', 'credit_exchange')->where('status', 'active')->first();
        if (!$listing) return response()->json(['error' => 'listing_unavailable'], 404);
        if ((int) $listing->seller_id === (int) $data['player_id']) return response()->json(['error' => 'cannot_buy_own'], 422);

        $buyer = DB::table('players')->where('id', $data['player_id'])->first();
        $seller = DB::table('players')->where('id', $listing->seller_id)->first();
        if (!$buyer || empty($buyer->wallet_address) || empty($buyer->wallet_verified_at)) return response()->json(['error' => 'buyer_no_wallet'], 422);
        if (!$seller || empty($seller->wallet_address) || empty($seller->wallet_verified_at)) return response()->json(['error' => 'seller_no_wallet'], 422);

        $amount = (float) $listing->price_hotel;
        $split = $this->hotel->splitFee($amount);
        $reference = 'CEX-' . Str::upper(Str::random(16));

        $swapId = DB::table('credit_exchange_swaps')->insertGetId([
            'listing_id' => $listing->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'buyer_wallet' => $buyer->wallet_address, 'seller_wallet' => $seller->wallet_address,
            'reference' => $reference, 'amount_hotel' => $amount, 'fee_hotel' => $split['fee'],
            'status' => 'intent', 'created_at' => now(),
        ]);

        return response()->json([
            'swap_id' => $swapId,
            'reference' => $reference,
            'mint' => $this->hotel->mint(),
            'decimals' => $this->hotel->decimals(),
            'amount_hotel' => $amount,
            'pay_seller' => $split['seller'],
            'pay_treasury' => $split['fee'],
            'seller_wallet' => $seller->wallet_address,
            'treasury_wallet' => $this->hotel->treasuryWallet(),
            'rpc_url' => $this->hotel->clientRpcUrl(),
            'expires_in' => (int) config('solana.hotel.intent_ttl_seconds', 900),
        ]);
    }

    // POST /api/internal/hotel/verify { reference, signature }
    public function verify(Request $request): JsonResponse
    {
        $this->assertInternal($request);
        if ($blocked = $this->guardEnabled()) return $blocked;

        $data = $request->validate(['reference' => 'required|string', 'signature' => 'required|string']);

        $swap = DB::table('credit_exchange_swaps')->where('reference', $data['reference'])->where('status', 'intent')->first();
        if (!$swap) return response()->json(['error' => 'unknown_or_settled_intent'], 404);

        $sellerAmount = (float) $swap->amount_hotel - (float) $swap->fee_hotel;
        $ok = $this->solana->verifyHotelSwap(
            $data['signature'], $this->hotel->mint(), $swap->buyer_wallet, $swap->seller_wallet,
            $this->hotel->treasuryWallet(), $sellerAmount, (float) $swap->fee_hotel, $swap->reference
        );

        if (!$ok) {
            return response()->json(['error' => 'verification_failed'], 422);
        }

        // Verified on-chain — settle atomically (lock the listing, deliver the item, log).
        try {
            DB::transaction(function () use ($swap, $data) {
                // Claim the listing (active -> sold) so it can only settle once.
                $claimed = DB::table('marketplace_listings')
                    ->where('id', $swap->listing_id)->where('status', 'active')
                    ->update(['status' => 'sold', 'buyer_id' => $swap->buyer_id, 'completed_at' => now()]);
                if (!$claimed) {
                    throw new \RuntimeException('listing_already_settled');
                }

                // Deliver the escrowed credit-item(s) to the buyer from the snapshot.
                $snapshots = DB::table('marketplace_listing_items')->where('listing_id', $swap->listing_id)->get();
                foreach ($snapshots as $snap) {
                    DB::table('player_furniture_items')->insert([
                        'player_id' => $swap->buyer_id, 'furniture_item_id' => $snap->furniture_item_id,
                        'limited_data' => $snap->limited_data, 'meta_data' => $snap->meta_data, 'created_at' => now(),
                    ]);
                }
                DB::table('marketplace_listing_items')->where('listing_id', $swap->listing_id)->delete();

                // Sale log + finalize the swap.
                $listing = DB::table('marketplace_listings')->where('id', $swap->listing_id)->first();
                DB::table('credit_exchange_sales')->insert([
                    'listing_id' => $swap->listing_id, 'furniture_item_id' => $listing->furniture_item_id,
                    'quantity' => $listing->quantity, 'total_credit_value' => $listing->total_credit_value,
                    'seller_id' => $swap->seller_id, 'buyer_id' => $swap->buyer_id,
                    'price_hotel' => $swap->amount_hotel, 'fee_hotel' => $swap->fee_hotel,
                    'tx_signature' => $data['signature'], 'sold_at' => now(),
                ]);
                DB::table('credit_exchange_swaps')->where('id', $swap->id)
                    ->update(['status' => 'confirmed', 'tx_signature' => $data['signature'], 'confirmed_at' => now()]);
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => 'settlement_failed', 'detail' => $e->getMessage()], 409);
        }

        return response()->json(['ok' => true, 'signature' => $data['signature']]);
    }
}
