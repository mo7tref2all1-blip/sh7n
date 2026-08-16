<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Api\V1\Driver\ShipmentController as DriverShipmentController;
use App\Http\Controllers\Api\V1\Merchant\ShipmentController as MerchantShipmentController;
use App\Http\Controllers\Public\TrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/public/track/{trackingNumber}', [TrackingController::class, 'apiShow']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::prefix('driver')->group(function () {
            Route::get('/dashboard', [DriverDashboardController::class, 'index']);
            Route::get('/shipments', [DriverShipmentController::class, 'index']);
            Route::get('/shipments/{shipment}', [DriverShipmentController::class, 'show']);
            Route::post('/shipments/{shipment}/scan-pickup', [DriverShipmentController::class, 'scanPickup']);
            Route::post('/shipments/{shipment}/deliver', [DriverShipmentController::class, 'deliver']);
            Route::post('/shipments/{shipment}/fail-attempt', [DriverShipmentController::class, 'failAttempt']);
            Route::post('/cash-handovers', [DriverShipmentController::class, 'handover']);
        });

        Route::prefix('merchant')->group(function () {
            Route::get('/shipments', [MerchantShipmentController::class, 'index']);
            Route::get('/shipments/{trackingNumber}', [MerchantShipmentController::class, 'show']);
            Route::post('/shipments', [MerchantShipmentController::class, 'store']);
            Route::post('/shipments/bulk', [MerchantShipmentController::class, 'storeBulk']);
        });
    });
});
