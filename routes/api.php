<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/posts.php';
    require __DIR__.'/api/v1/admin.php';
    require __DIR__.'/api/v1/blog.php';
    require __DIR__.'/api/v1/orders.php';
});
