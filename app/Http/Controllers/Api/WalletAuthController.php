<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletChallenge;
use App\Services\SolanaVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletAuthController extends Controller
{
    public function __construct(
        private SolanaVerificationService $solana
    ) {}

    public function challenge(Request $request): JsonResponse
    {
        $request->validate([
            'wallet_address' => ['required', 'string', 'min:32', 'max:44'],
        ]);

        $wallet = $request->wallet_address;

        if (!$this->solana->isValidWalletAddress($wallet)) {
            return response()->json(['error' => 'Invalid wallet address.'], 422);
        }

        WalletChallenge::where('wallet_address', $wallet)->delete();

        $nonce = sprintf(
            'Sign this message to login to Solana Hotel. Nonce: %s',
            Str::random(32)
        );

        WalletChallenge::create([
            'wallet_address' => $wallet,
            'nonce'          => $nonce,
            'expires_at'     => now()->addMinutes(2),
        ]);

        return response()->json(['nonce' => $nonce]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'wallet_address' => ['required', 'string', 'min:32', 'max:44'],
            'signature'      => ['required', 'string'],
        ]);

        $wallet    = $request->wallet_address;
        $signature = $request->signature;

        $challenge = WalletChallenge::active()
            ->where('wallet_address', $wallet)
            ->first();

        if (!$challenge) {
            return response()->json([
                'error' => 'Challenge not found or expired. Please try again.'
            ], 401);
        }

        $valid = $this->solana->verifySignature($wallet, $challenge->nonce, $signature);

        $challenge->delete();

        if (!$valid) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $user = User::where('wallet_address', $wallet)->first();

        if ($user) {
            Auth::login($user, remember: true);
            $user->update(['wallet_verified_at' => now()]);

            return response()->json([
                'status'   => 'authenticated',
                'username' => $user->username,
            ]);
        }

        session(['pending_wallet' => $wallet]);

        return response()->json(['status' => 'registration_required']);
    }

    public function register(Request $request): JsonResponse
    {
        $wallet = session('pending_wallet');

        if (!$wallet) {
            return response()->json([
                'error' => 'Session expired. Please reconnect your wallet.'
            ], 401);
        }

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:25',
                'alpha_num',
                'unique:players,username',
            ],
        ]);

        if (User::where('wallet_address', $wallet)->exists()) {
            session()->forget('pending_wallet');
            return response()->json([
                'error' => 'This wallet is already registered.'
            ], 409);
        }

        $user = DB::transaction(function () use ($wallet, $request) {
            $user = User::create([
                'username'           => $request->username,
                'wallet_address'     => $wallet,
                'wallet_verified_at' => now(),
                'email'              => substr($wallet, 0, 20) . '@wallet.sol',
                'password'           => bcrypt(Str::random(64)),
                'created_at'         => now(),
                'website_balance'    => 0,
            ]);

            // The Sadie emulator can't resolve a player without these profile rows,
            // and needs a room to spawn into. Create the full game profile + a
            // personal home room so the player can enter the hotel immediately.

            // Reuse an existing room model, or create a default 9x9 one.
            $layoutId = DB::table('room_layouts')->value('id');
            if (! $layoutId) {
                $layoutId = DB::table('room_layouts')->insertGetId([
                    'name'                     => 'model_a',
                    'heightmap'                => implode("\r", array_fill(0, 9, '000000000')),
                    'door_x'                   => 4,
                    'door_y'                   => 4,
                    'door_direction'           => 2,
                    'requires_club_membership' => 0,
                    'extra_data'               => null,
                ]);
            }

            // Personal home room: "{username}'s Welcome Lounge"
            $roomId = DB::table('rooms')->insertGetId([
                'name'              => $user->username . "'s Welcome Lounge",
                'layout_id'         => $layoutId,
                'owner_id'          => $user->id,
                'max_users_allowed' => 50,
                'description'       => 'Welcome to my room!',
                'is_muted'          => 0,
                'created_at'        => now(),
            ]);
            DB::table('room_settings')->insert(['room_id' => $roomId, 'access_type' => 0, 'trade_option' => 2]);
            DB::table('room_paint_settings')->insert(['room_id' => $roomId]);
            DB::table('room_chat_settings')->insert(['room_id' => $roomId]);

            // Emulator player profile rows
            DB::table('player_data')->insert([
                'player_id'             => $user->id,
                'home_room_id'          => $roomId,
                'credit_balance'        => 1000,
                'pixel_balance'         => 1000,
                'seasonal_balance'      => 0,
                'gotw_points'           => 0,
                'respect_points'        => 15,
                'respect_points_pet'    => 15,
                'achievement_score'     => 0,
                'allow_friend_requests' => 1,
                'is_online'             => 0,
                'last_online'           => null,
            ]);
            DB::table('player_avatar_data')->insert([
                'player_id'      => $user->id,
                'figure_code'    => 'hd-180-1.ch-255-66.lg-280-110.sh-305-62',
                'motto'          => 'Newbie at the hotel!',
                'gender'         => 'M',
                'chat_bubble_id' => 0,
            ]);
            DB::table('player_game_settings')->insert(['player_id' => $user->id]);
            DB::table('player_navigator_settings')->insert(['player_id' => $user->id]);

            return $user;
        });

        session()->forget('pending_wallet');

        Auth::login($user, remember: true);

        return response()->json([
            'status'   => 'registered',
            'username' => $user->username,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'logged_out']);
    }
}