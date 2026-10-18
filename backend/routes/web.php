<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\ManagerDashboardController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/chat', function () {
    return view('chat');
});
Route::get('/onboarding/{sessionId}', function ($sessionId) {
    $session = \App\Models\OnboardingSession::findOrFail($sessionId);
    return view('chat', ['sessionId' => $session->id, 'agentId' => $session->agent_id]);
});
Route::get('/agent', [AgentDashboardController::class, 'index']);
Route::get('/agent/{agentId}', [AgentDashboardController::class, 'show']);
Route::post('/agent/{agentId}/sessions', [AgentDashboardController::class, 'startSession']);

Route::get('/manager', [ManagerDashboardController::class, 'index']);
Route::get('/manager/sessions/{sessionId}', [ManagerDashboardController::class, 'session']);
Route::post('/manager/alerts/{alertId}/resolve', [ManagerDashboardController::class, 'resolve']);
