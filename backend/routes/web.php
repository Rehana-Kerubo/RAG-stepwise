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
Route::post('/profile/region', [ProfileController::class, 'updateRegion'])->middleware(['auth', 'role:agent']);

// Agent pages and the endpoints the wizard calls
Route::middleware(['auth', 'role:agent'])->group(function () {
    Route::get('/agent', [AgentDashboardController::class, 'show']);
    Route::get('/agent/onboardings', [AgentDashboardController::class, 'onboardings']);
    Route::post('/agent/sessions', [AgentDashboardController::class, 'startSession']);
    Route::get('/onboarding/{sessionId}', [AgentDashboardController::class, 'onboarding']);

    Route::get('/wizard/sessions/{sessionId}/steps', [StepProgressController::class, 'index']);
    Route::post('/wizard/sessions/{sessionId}/steps/{stepId}/submit', [StepProgressController::class, 'submit']);
    Route::get('/wizard/steps/{stepId}/guidance', [StepProgressController::class, 'guidance']);
    // At most 10 questions a minute per agent: the AI runs on a CPU and is slow.
    Route::post('/wizard/ask', [ChatController::class, 'ask'])->middleware('throttle:10,1');
});

// Manager pages
Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/manager', [ManagerDashboardController::class, 'index']);
    Route::get('/manager/agents', [ManagerDashboardController::class, 'agents']);
    Route::get('/manager/agents/create', [ManagerDashboardController::class, 'createAgent']);
    Route::post('/manager/agents', [ManagerDashboardController::class, 'storeAgent']);
    Route::get('/manager/agents/{agentId}', [ManagerDashboardController::class, 'agent']);
    Route::post('/manager/agents/{agentId}/status', [ManagerDashboardController::class, 'setAgentStatus']);
    Route::post('/manager/agents/{agentId}/gigs/{gigId}', [ManagerDashboardController::class, 'assignGig']);
    Route::get('/manager/alerts', [ManagerDashboardController::class, 'alerts']);
    Route::get('/manager/insights', [ManagerDashboardController::class, 'insights']);
    Route::get('/manager/sessions/{sessionId}', [ManagerDashboardController::class, 'session']);
    Route::post('/manager/alerts/{alertId}/resolve', [ManagerDashboardController::class, 'resolve']);
});
