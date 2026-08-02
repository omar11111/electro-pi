<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('projects', ProjectController::class);

    // shallow() => GET/POST use /projects/{project}/tasks,
    // while show/update/destroy collapse to /tasks/{task} directly.
    Route::apiResource('projects.tasks', TaskController::class)->shallow();
});
