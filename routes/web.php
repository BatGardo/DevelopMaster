<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\PositionController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ✅ доступні всім авторизованим
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/notifications', [HomeController::class, 'notifications'])->name('notifications.index');
    Route::get('/profile', [HomeController::class, 'profile'])->name('profile.index');

    // 📚 Позиції — лише адміністратор
    Route::middleware('role:admin')->group(function () {
        Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
        Route::get('/positions/create', [PositionController::class, 'create'])->name('positions.create');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
    });

    // 📁 Справи
    // список/реєстр — admin, executor
    Route::get('/cases', [CaseController::class, 'index'])
        ->middleware('role:admin,executor')
        ->name('cases.index');

    // створення — admin, executor, applicant
    Route::get('/cases/create', [CaseController::class, 'create'])
        ->middleware('role:admin,executor,applicant')
        ->name('cases.create');
    Route::post('/cases', [CaseController::class, 'store'])
        ->middleware('role:admin,executor,applicant')
        ->name('cases.store');

    // перегляд конкретної справи та операції — Gate перевіряється в контролері
    Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');
    Route::post('/cases/{case}/actions', [CaseController::class, 'addAction'])->name('cases.actions.store');
    Route::post('/cases/{case}/upload', [CaseController::class, 'uploadDocument'])->name('cases.documents.store');

Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])
    ->middleware(['auth','can:view-analytics'])
    ->name('analytics.index');
});
