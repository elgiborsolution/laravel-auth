<?php

use ElgiborSolution\Authentication\Http\Controllers\AuthController;
use ElgiborSolution\Authentication\Http\Controllers\PermissionController;
use ElgiborSolution\Authentication\Http\Controllers\RoleController;
use ElgiborSolution\Authentication\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('api/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('api/logout', [AuthController::class, 'logout']);
    Route::get('api/me', [AuthController::class, 'me']);
    Route::post('api/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('api/user/{uuid}/reset-password', [AuthController::class, 'resetOtherUserPassword']);

    // Two-Step Login: step 2 (tenant login) — only registered when enabled
    if (config('authentication.two_step_login.enabled', false)) {
        Route::post(
            config('authentication.two_step_login.tenant_login_path', 'api/tenant-login'),
            [AuthController::class, 'tenantLogin']
        );

    }

    Route::get('api/user/password-status/{uuid}', [UserController::class, 'passwordStatus']);

    // Roles
    Route::apiResource('api/roles', RoleController::class);
    Route::put('api/roles/status/{id}', [RoleController::class, 'status']);

    // Permissions
    Route::apiResource('api/permissions', PermissionController::class)->only(['index']);
    Route::patch('api/permissions/{id}/toggle-status', [PermissionController::class, 'toggleStatus']);
});
