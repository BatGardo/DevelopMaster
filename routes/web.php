<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Приклади захищених сторінок під АСВП
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/cases', [HomeController::class, 'cases'])->name('cases.index');
    Route::get('/analytics', [HomeController::class, 'analytics'])->name('analytics.index');
    Route::get('/notifications', [HomeController::class, 'notifications'])->name('notifications.index');
    Route::get('/profile', [HomeController::class, 'profile'])->name('profile.index');
});
