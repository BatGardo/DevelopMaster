<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PositionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/notifications', [HomeController::class, 'notifications'])->name('notifications.index');
    Route::get('/profile', [HomeController::class, 'profile'])->name('profile.index');

    Route::middleware('role:admin')->group(function () {
        Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
        Route::get('/positions/create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });

    Route::middleware('role:admin,executor')->group(function () {
        Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    });

    Route::middleware('role:admin,executor,applicant')->group(function () {
        Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
        Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    });

    Route::get('/cases/my', [CaseController::class, 'mine'])
        ->middleware('role:admin,executor,viewer,applicant')
        ->name('cases.mine');

    Route::get('/cases/{case}', [CaseController::class, 'show'])
        ->middleware('role:admin,executor,viewer,applicant')
        ->name('cases.show');
    Route::post('/cases/{case}/actions', [CaseController::class, 'addAction'])
        ->middleware('role:admin,executor')
        ->name('cases.actions.store');
    Route::post('/cases/{case}/upload', [CaseController::class, 'uploadDocument'])
        ->middleware('role:admin,executor')
        ->name('cases.documents.store');

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('can:view-analytics')
        ->name('analytics.index');
});

