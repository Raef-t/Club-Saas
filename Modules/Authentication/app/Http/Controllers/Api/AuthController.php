<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\User;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\Core\Http\Controllers\Api\BaseController;
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
        description: 'Login successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'object', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcde...')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Invalid credentials')]
    #[OA\Response(response: 403, description: 'Account inactive')]
    public function login(LoginRequest $request)
    {
        $user = User::where('username', $request->username)
            ->where('tenant_id', session('tenant_id'))
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse(__('Invalid credentials'), 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse(__('Account is inactive. Please contact admin.'), 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user->load('person')),
            'token' => $token,
        ], __('Login successful'));
    }

    #[OA\Post(
        path: '/v1/auth/logout',
        summary: '🚪 User Logout',
        description: 'Revoke the current user session and destroy the token.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Logged out successfully')]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, __('Logged out successfully'));
    }
}
