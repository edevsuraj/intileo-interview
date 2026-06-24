<?php

use App\Http\Controllers\V1\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blogs')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blogs.index');
});