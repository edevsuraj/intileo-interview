<?php

use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->name('users.')->controller(UserController::class)->group(function () {
    Route::post('/register', 'store')->name('register');
    Route::post('/login', 'login')->name('login');

    Route::middleware('api.token')->group(function () {
        Route::get('/me', 'show')->name('show');
        Route::post('/logout', 'logout')->name('logout');
    });
});
