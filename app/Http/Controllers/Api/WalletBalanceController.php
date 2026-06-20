<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HotelTokenService;
use App\Services\SolanaPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Public, throttled lookup of a player's on-chain balances (SOL + $HOTEL) by username, for the
// in-client purse display. On-chain balances are public; the RPC + mint stay server-side. Cached
// briefly so polling clients don't hammer the RPC.
class WalletBalanceController extends Controller
{
    public function __construct(
        private readonly SolanaPaymentService $solana,
        private readonly HotelTokenService $hotel,
    ) {
    }

    // GET /api/wallet/balances?username=NAME
    public function balances(Request $request): JsonResponse
    {
        $username = (string) $request->query('username', '');
        if ($username === '') {
            return response()->json(['error' => 'username_required'], 422);
        }

        $wallet = DB::table('players')->where('username', $username)->value('wallet_address');
        if (empty($wallet)) {
            return response()->json(['wallet' => null, 'sol' => 0, 'hotel' => 0, 'hotelConfigured' => $this->hotel->mint() !== '']);
        }

        $data = Cache::remember("wallet_balances:$wallet", 30, function () use ($wallet) {
            $sol = 0.0;
            $hotelBal = 0.0;
            try { $sol = $this->solana->getSolBalance($wallet); } catch (\Throwable $e) {}
            if ($this->hotel->mint() !== '') {
                try { $hotelBal = $this->solana->getTokenBalance($wallet, $this->hotel->mint()); } catch (\Throwable $e) {}
            }
            return ['sol' => $sol, 'hotel' => $hotelBal];
        });

        return response()->json([
            'wallet' => $wallet,
            'sol' => round($data['sol'], 4),
            'hotel' => round($data['hotel'], 2),
            'hotelConfigured' => $this->hotel->mint() !== '',
        ]);
    }
}
