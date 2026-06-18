<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClubPackage;
use App\Models\ClubPayment;
use App\Services\SolanaPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Internal (emulator -> CMS, shared-secret) endpoints for Solana Club payments.
// The emulator owns the player session and grants membership; this just issues
// payment intents and verifies finalized on-chain payments. Never trusts the client.
class ClubPaymentController extends Controller
{
    public function __construct(private readonly SolanaPaymentService $solana)
    {
    }

    // GET /api/club/packages  (public, read-only) — list + live SOL conversion for display.
    public function packages(): JsonResponse
    {
        $rate = null;
        try {
            $rate = $this->solana->getSolUsdRate();
        } catch (\Throwable $e) {
            // price unavailable — still return packages without SOL estimate
        }

        $packages = ClubPackage::where('enabled', true)->orderBy('order_num')->get()->map(function ($p) use ($rate) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'months' => $p->months,
                'duration_days' => $p->duration_days,
                'price_usd' => (float) $p->price_usd,
                'sol' => $rate ? round(((float) $p->price_usd) / $rate, 4) : null,
            ];
        });

        return response()->json([
            'packages' => $packages,
            'sol_usd_rate' => $rate,
            'network' => config('solana.network'),
            'treasury' => config('solana.treasury_wallet'),
            'rpc_url' => config('solana.client_rpc_url'),
        ]);
    }

    private function assertInternal(Request $request): void
    {
        $secret = (string) config('solana.internal_secret');

        if ($secret === '' || !hash_equals($secret, (string) $request->header('X-Internal-Secret', ''))) {
            abort(403, 'forbidden');
        }
    }

    // POST /api/internal/club/intent  { player_id, package_id }
    public function intent(Request $request): JsonResponse
    {
        $this->assertInternal($request);

        $data = $request->validate([
            'player_id' => 'required|integer',
            'package_id' => 'required|integer',
        ]);

        $package = ClubPackage::where('id', $data['package_id'])->where('enabled', true)->first();
        if (!$package) {
            return response()->json(['error' => 'unknown_package'], 404);
        }

        $player = DB::table('players')->where('id', $data['player_id'])->first();
        if (!$player || empty($player->wallet_address) || empty($player->wallet_verified_at)) {
            return response()->json(['error' => 'no_linked_wallet'], 422);
        }

        try {
            $rate = $this->solana->getSolUsdRate();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'price_unavailable'], 503);
        }

        $lamports = $this->solana->usdToLamports((float) $package->price_usd, $rate);
        $treasury = (string) config('solana.treasury_wallet');
        $reference = 'club_' . Str::random(40);

        ClubPayment::create([
            'player_id' => $data['player_id'],
            'wallet_address' => $player->wallet_address,
            'package_id' => $package->id,
            'reference' => $reference,
            'expected_lamports' => $lamports,
            'price_usd' => $package->price_usd,
            'sol_usd_rate' => $rate,
            'status' => 'pending',
            'treasury_wallet' => $treasury,
        ]);

        return response()->json([
            'treasury' => $treasury,
            'lamports' => $lamports,
            'sol' => $lamports / 1_000_000_000,
            'reference' => $reference,
            'network' => config('solana.network'),
            'price_usd' => (float) $package->price_usd,
            'sol_usd_rate' => $rate,
        ]);
    }

    // POST /api/internal/club/verify  { player_id, package_id, signature }
    public function verify(Request $request): JsonResponse
    {
        $this->assertInternal($request);

        $data = $request->validate([
            'player_id' => 'required|integer',
            'package_id' => 'required|integer',
            'signature' => 'required|string|max:128',
        ]);

        // Anti-replay: a signature can be redeemed at most once.
        if (ClubPayment::where('signature', $data['signature'])->where('status', 'verified')->exists()) {
            return response()->json(['ok' => false, 'error' => 'already_redeemed']);
        }

        $ttl = (int) config('solana.intent_ttl_seconds', 900);
        $payment = ClubPayment::where('player_id', $data['player_id'])
            ->where('package_id', $data['package_id'])
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subSeconds($ttl))
            ->latest('id')
            ->first();

        if (!$payment) {
            return response()->json(['ok' => false, 'error' => 'no_pending_intent']);
        }

        $ok = $this->solana->verifyPayment(
            $data['signature'],
            $payment->treasury_wallet,
            (int) $payment->expected_lamports,
            $payment->wallet_address,
            $payment->reference,
            (int) config('solana.amount_tolerance_bps', 50),
        );

        if (!$ok) {
            $payment->update(['status' => 'failed']);
            \Log::warning('club-pay: verification FAILED', ['player' => $data['player_id'], 'package' => $data['package_id'], 'sig' => $data['signature']]);
            return response()->json(['ok' => false, 'error' => 'verification_failed']);
        }

        try {
            $payment->update([
                'signature' => $data['signature'],
                'status' => 'verified',
                'verified_at' => now(),
            ]);
        } catch (QueryException $e) {
            // unique(signature) violation = concurrent redeem of the same tx
            return response()->json(['ok' => false, 'error' => 'already_redeemed']);
        }

        $package = ClubPackage::find($payment->package_id);

        \Log::info('club-pay: GRANTED', ['player' => $data['player_id'], 'package' => $package->name, 'days' => $package->duration_days, 'sig' => $data['signature']]);

        return response()->json([
            'ok' => true,
            'duration_days' => (int) $package->duration_days,
            'months' => (int) $package->months,
        ]);
    }
}
