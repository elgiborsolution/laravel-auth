<?php

use Illuminate\Support\Facades\Route;
use ElgiborSolution\Authentication\Http\Controllers\AuthController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Roles
    Route::apiResource('roles', \ElgiborSolution\Authentication\Http\Controllers\RoleController::class);
    
    // Permissions
    Route::apiResource('permissions', \ElgiborSolution\Authentication\Http\Controllers\PermissionController::class)->only(['index']);
    Route::patch('permissions/{id}/toggle-status', [\ElgiborSolution\Authentication\Http\Controllers\PermissionController::class, 'toggleStatus']);
});
