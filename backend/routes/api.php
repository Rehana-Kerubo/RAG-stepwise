<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StepProgressController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ask', [ChatController::class, 'ask']);

Route::get('/sessions/{sessionId}/steps', [StepProgressController::class, 'index']);
Route::post('/sessions/{sessionId}/steps/{stepId}/submit', [StepProgressController::class, 'submit']);
Route::post('/sessions/{sessionId}/steps/{stepId}/feedback', [StepProgressController::class, 'feedback']);