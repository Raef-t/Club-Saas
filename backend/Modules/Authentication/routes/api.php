<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\Api\AuthController;
use Modules\Authentication\Http\Controllers\Api\PersonContactController;
use Modules\Authentication\Http\Controllers\Api\V1\PermissionController;
use Modules\Authentication\Http\Controllers\Api\V1\RoleController;
use Modules\Authentication\Http\Controllers\Api\V1\UserRoleController;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('set-custom-username', [AuthController::class, 'setCustomUsername']);
        Route::post('change-photo', [AuthController::class, 'updatePhoto']);
        Route::delete('delete-photo', [AuthController::class, 'deletePhoto']);
    });
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // ─── Contacts ──────────────────────────────────────────────────────────────
    Route::apiResource('contacts', PersonContactController::class);

    // ─── Permissions (read-only) ────────────────────────────────────────────────
    // GET /v1/permissions             → all permissions
    // GET /v1/permissions?module=member → filtered by module
    Route::get('permissions', [PermissionController::class, 'index']);

    // ─── Roles (CRUD + Sync Permissions) ────────────────────────────────────────
    // GET    /v1/roles                → list all roles
    // POST   /v1/roles                → create new role
    // GET    /v1/roles/{id}           → show role with permissions
    // PUT    /v1/roles/{id}/permissions → sync permissions for role
    // DELETE /v1/roles/{id}           → delete role
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::get('roles/{id}', [RoleController::class, 'show']);
    Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);
    Route::delete('roles/{id}', [RoleController::class, 'destroy']);

    // ─── User Roles ─────────────────────────────────────────────────────────────
    // GET    /v1/users/{userId}/roles → get user's roles & permissions
    // POST   /v1/users/{userId}/roles → assign role to user
    // DELETE /v1/users/{userId}/roles → revoke role from user
    Route::get('users/{userId}/roles', [UserRoleController::class, 'index']);
    Route::post('users/{userId}/roles', [UserRoleController::class, 'assign']);
    Route::delete('users/{userId}/roles', [UserRoleController::class, 'revoke']);
});
