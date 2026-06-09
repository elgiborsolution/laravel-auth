<?php

use Illuminate\Support\Facades\Route;
use ElgiborSolution\Authentication\Http\Controllers\AuthController;

Route::post('api/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('api/logout', [AuthController::class, 'logout']);
    Route::get('api/me', [AuthController::class, 'me']);

    // Roles
    Route::apiResource('api/roles', \ElgiborSolution\Authentication\Http\Controllers\RoleController::class);
    
    // Permissions
    Route::apiResource('api/permissions', \ElgiborSolution\Authentication\Http\Controllers\PermissionController::class)->only(['index']);
    Route::patch('api/permissions/{id}/toggle-status', [\ElgiborSolution\Authentication\Http\Controllers\PermissionController::class, 'toggleStatus']);
});
