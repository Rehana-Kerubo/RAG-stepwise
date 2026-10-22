<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The wizard's endpoints moved to routes/web.php (behind login and CSRF).

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
