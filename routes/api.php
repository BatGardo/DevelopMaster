<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/user', function () {
    return Auth::user();
})->middleware('auth:sanctum');
