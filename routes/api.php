<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AlertApiController;

Route::post('/alerts/plan', [AlertApiController::class, 'storePlan']);
Route::post('/alerts/{alertId}/result', [AlertApiController::class, 'storeResult']);

Route::post('/alerts/{alert}/verify-plan', [AlertApiController::class, 'verifyPlan'])
    ->name('api.alerts.verify-plan');