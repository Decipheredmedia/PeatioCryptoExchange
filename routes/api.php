<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\{
    MarketsController,
    OrdersController,
    TradesController,
    DepositsController,
    WithdrawsController,
    AccountsController,
    MembersController
};

Route::prefix('v2')->middleware(['auth:api', 'throttle:60,1'])->group(function () {
    // Markets
    Route::get('/markets', [MarketsController::class, 'index']);
    Route::get('/markets/{id}', [MarketsController::class, 'show']);
    Route::get('/markets/{id}/tickers', [MarketsController::class, 'tickers']);
    Route::get('/markets/{id}/depth', [MarketsController::class, 'depth']);
    Route::get('/markets/{id}/trades', [MarketsController::class, 'trades']);
    
    // Orders
    Route::get('/orders', [OrdersController::class, 'index']);
    Route::post('/orders', [OrdersController::class, 'store']);
    Route::get('/orders/{id}', [OrdersController::class, 'show']);
    Route::delete('/orders/{id}', [OrdersController::class, 'destroy']);
    Route::delete('/orders', [OrdersController::class, 'destroyAll']);
    
    // Trades
    Route::get('/trades', [TradesController::class, 'index']);
    Route::get('/trades/my', [TradesController::class, 'my']);
    
    // Accounts
    Route::get('/accounts', [AccountsController::class, 'index']);
    Route::get('/accounts/{currency}', [AccountsController::class, 'show']);
    
    // Deposits
    Route::get('/deposits', [DepositsController::class, 'index']);
    Route::get('/deposits/{id}', [DepositsController::class, 'show']);
    Route::get('/deposit_address/{currency}', [DepositsController::class, 'depositAddress']);
    
    // Withdraws
    Route::get('/withdraws', [WithdrawsController::class, 'index']);
    Route::post('/withdraws', [WithdrawsController::class, 'store']);
    Route::get('/withdraws/{id}', [WithdrawsController::class, 'show']);
    
    // Members
    Route::get('/members/me', [MembersController::class, 'me']);
});

// Public endpoints
Route::prefix('v2')->group(function () {
    Route::get('/timestamp', function () {
        return response()->json(['timestamp' => time()]);
    });
    
    Route::get('/currencies', [MarketsController::class, 'currencies']);
});
