<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\Api\V1\RecurringTransactionController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/openapi', OpenApiController::class);

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::put('/user', [AuthController::class, 'updateProfile']);
            Route::put('/password', [AuthController::class, 'updatePassword']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('wallets', WalletController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::get('transactions/summary', [TransactionController::class, 'summary']);
        Route::apiResource('transactions', TransactionController::class);

        Route::get('budgets/overview', [BudgetController::class, 'overview']);
        Route::apiResource('budgets', BudgetController::class);

        Route::post('recurring-transactions/{recurringTransaction}/skip', [RecurringTransactionController::class, 'skip']);
        Route::post('recurring-transactions/{recurringTransaction}/process', [RecurringTransactionController::class, 'processNow']);
        Route::apiResource('recurring-transactions', RecurringTransactionController::class);

        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
    });
});
