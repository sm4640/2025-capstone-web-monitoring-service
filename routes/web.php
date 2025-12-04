<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlertController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth.wms')->group(function () {
    Route::get('/', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/{id}', [AlertController::class, 'show'])->name('alerts.show');
    Route::post('/alerts/{id}/action', [AlertController::class, 'action'])->name('alerts.action');
});