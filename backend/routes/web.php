<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StepProgressController;

// Login (only for people who are not logged in yet)
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

    // Password login for the seeded demo accounts exists only on local.
    if (app()->isLocal()) {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    }
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Profile tab (agents and managers)
Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth');

// Agent pages and the endpoints the wizard calls
Route::middleware(['auth', 'role:agent'])->group(function () {
    Route::get('/chat', function () {
        return view('chat');
    });
    Route::get('/agent', [AgentDashboardController::class, 'show']);
    Route::get('/agent/onboardings', [AgentDashboardController::class, 'onboardings']);
    Route::post('/agent/sessions', [AgentDashboardController::class, 'startSession']);
    Route::get('/onboarding/{sessionId}', [AgentDashboardController::class, 'onboarding']);

    Route::get('/wizard/sessions/{sessionId}/steps', [StepProgressController::class, 'index']);
    Route::post('/wizard/sessions/{sessionId}/steps/{stepId}/submit', [StepProgressController::class, 'submit']);
    Route::get('/wizard/steps/{stepId}/guidance', [StepProgressController::class, 'guidance']);
    Route::post('/wizard/ask', [ChatController::class, 'ask']);
});

// Manager pages
Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/manager', [ManagerDashboardController::class, 'index']);
    Route::get('/manager/agents', [ManagerDashboardController::class, 'agents']);
    Route::get('/manager/alerts', [ManagerDashboardController::class, 'alerts']);
    Route::get('/manager/sessions/{sessionId}', [ManagerDashboardController::class, 'session']);
    Route::post('/manager/alerts/{alertId}/resolve', [ManagerDashboardController::class, 'resolve']);
});
