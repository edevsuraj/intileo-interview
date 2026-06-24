<?php

use App\Http\Controllers\V1\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->controller(AdminController::class)->group(function () {
    Route::get('/fetch-admin', 'index')->name('index');
});
