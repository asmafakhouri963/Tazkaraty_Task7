<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\EventController;

/*
|--------------------------------------------------------------------------
| API Routes - V1 (Tazkaraty Platform)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // مسارات الشراء والحجز المؤقت
    Route::post('/bookings', [PurchaseController::class, 'store']);
    Route::post('/bookings/{id}/pay', [PurchaseController::class, 'pay']);

    // مسار إلغاء الفعالية (Event-Driven Cancel Action)
    Route::post('/events/{event}/cancel', [EventController::class, 'cancel']);
});