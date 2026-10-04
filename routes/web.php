<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\MaintenanceRiskController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\TelematicsController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('vehicles', VehicleController::class);
    // Same resource routes as vehicles: index, create, store, show, edit, update, destroy.
    Route::resource('drivers', DriverController::class);
    Route::get('/telematics', [TelematicsController::class, 'index'])->name('telematics.index');
    Route::get('/analytics', AnalyticsController::class)->name('analytics.index');
    Route::get('/maintenance-risk', [MaintenanceRiskController::class, 'index'])->name('risks.index');
    Route::get('/maintenance-risk/{vehicle}', [MaintenanceRiskController::class, 'show'])->name('risks.show');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::patch('/alerts/{alert}', [AlertController::class, 'update'])->name('alerts.update');
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
    Route::patch('/recommendations/{recommendation}', [RecommendationController::class, 'update'])->name('recommendations.update');
});
