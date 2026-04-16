<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\WorkApplicationController;
use App\Http\Controllers\Api\WorkController;
use Illuminate\Support\Facades\Route;

// Public endpoints
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/works', [WorkController::class, 'index']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Authenticated endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Company work management
    Route::get('/company/works', [WorkController::class, 'companyIndex']);
    Route::post('/works', [WorkController::class, 'store']);
    Route::put('/works/{work_id}', [WorkController::class, 'update']);

    // Work applications
    Route::get('/works/{work_id}/applications', [WorkApplicationController::class, 'index']);
    Route::get('/works/{work_id}/applications/{application_id}', [WorkApplicationController::class, 'show']);
    Route::post('/works/{work_id}/applications', [WorkApplicationController::class, 'store']);
});
