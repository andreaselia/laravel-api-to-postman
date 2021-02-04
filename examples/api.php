<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::apiResource('posts', 'PostController');

Route::middleware('auth:api')->group(function () {
    Route::post('/user', UserController::class)->name('user');
});
