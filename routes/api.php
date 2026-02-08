<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MeetingController;

// Simple health endpoint for the API root
Route::get('/', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is running']);
});

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Meetings
    Route::apiResource('meetings', MeetingController::class);
    Route::post('/meetings/{meeting}/join', [MeetingController::class, 'join']);
    Route::post('/meetings/{meeting}/leave', [MeetingController::class, 'leave']);

    // Admin - User Management
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::put('/users/{user}/role', [App\Http\Controllers\UserController::class, 'updateRole']);
});
