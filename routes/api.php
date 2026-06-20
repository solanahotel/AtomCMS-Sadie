<?php

use App\Http\Controllers\Api\HotelApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user/{username}', [HotelApiController::class, 'fetchUser'])->name('api.fetch-user')->middleware('throttle:50,1');
Route::get('/online-users', [HotelApiController::class, 'onlineUsers'])->name('api.online-users')->middleware('throttle:50,1');
Route::get('/online-count', [HotelApiController::class, 'onlineUserCount'])->name('api.online-count')->middleware('throttle:50,1');

use App\Http\Controllers\Api\WalletAuthController;

Route::prefix('wallet')->group(function () {
    Route::get('/balances', [\App\Http\Controllers\Api\WalletBalanceController::class, 'balances'])->middleware('throttle:120,1');
    Route::post('/challenge', [WalletAuthController::class, 'challenge']);
    Route::post('/verify',    [WalletAuthController::class, 'verify']);
    Route::post('/register',  [WalletAuthController::class, 'register']);
    Route::post('/logout',    [WalletAuthController::class, 'logout']);
});

use App\Http\Controllers\Api\ClubPaymentController;

// Public: package list for the in-game catalog (display only).
Route::get('/club/packages', [ClubPaymentController::class, 'packages'])->middleware('throttle:60,1');

// Internal: called server-to-server by the emulator (X-Internal-Secret). Not for clients.
Route::prefix('internal/hotel')->group(function () {
    Route::post('/intent', [\App\Http\Controllers\Api\CreditExchangeController::class, 'intent']);
    Route::post('/verify', [\App\Http\Controllers\Api\CreditExchangeController::class, 'verify']);
});

Route::prefix('internal/club')->group(function () {
    Route::post('/intent', [ClubPaymentController::class, 'intent']);
    Route::post('/verify', [ClubPaymentController::class, 'verify']);
});