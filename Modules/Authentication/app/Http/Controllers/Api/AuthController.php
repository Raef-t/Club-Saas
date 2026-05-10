<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    #[OA\Post(
        path: '/v1/auth/login',
        summary: '🔐 User Login & Token Generation',
        description: 'Authenticate a user and return a Bearer Token. Requires a valid Username, Password, and the Tenant (Club) ID in the headers.',
        tags: ['Authentication']
    )]
    #[OA\Parameter(
        name: 'X-Tenant-ID',
        in: 'header',
        description: 'The unique ID of the Club/Tenant',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'User credentials',
        content: new OA\JsonContent(
            required: ['username', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'Unique username of the staff or admin', example: 'admin'),
                new OA\Property(property: 'password', type: 'string', description: 'User password', example: 'password123'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ Successfully Authenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Logged in successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', example: '1|abc123token...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'username', type: 'string', example: 'admin'),
                                new OA\Property(property: 'full_name', type: 'string', example: 'Admin User'),
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ Invalid Credentials',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 Account Inactive',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
    )]
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse(__('Invalid credentials'), 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse(__('User account is inactive'), 403);
        }

        // Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->person->full_name ?? null,
            ]
        ], __('Logged in successfully'));
    }

    #[OA\Post(
        path: '/v1/auth/logout',
        summary: '🚪 User Logout',
        description: 'Revoke the current user access token and end the session.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'X-Tenant-ID',
        in: 'header',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ Logged out successfully',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, __('Logged out successfully'));
    }
}
