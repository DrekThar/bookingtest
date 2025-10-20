<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

Route::get('/', [BookingController::class, 'index'])
    ->name('home');
Route::get('/services/{service}/show', [BookingController::class, 'show'])
    ->name('service.show');
Route::get('/services/{service}/slots', [BookingController::class, 'getAvailableSlots'])
    ->name('slots.get');
Route::post('/bookings', [BookingController::class, 'store'])
    ->name('bookings.store');
