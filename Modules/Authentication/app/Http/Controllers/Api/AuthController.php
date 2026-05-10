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
    #[OA\Post(path: '/v1/auth/login', summary: 'User Login', tags: ['Authentication'])]
    #[OA\Parameter(name: 'X-Tenant-ID', in: 'header', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['username', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', example: 'admin'),
                new OA\Property(property: 'password', type: 'string', example: 'password123'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Successful login')]
    #[OA\Response(response: 401, description: 'Invalid credentials')]
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

    #[OA\Post(path: '/v1/auth/logout', summary: 'User Logout', security: [['bearerAuth' => []]], tags: ['Authentication'])]
    #[OA\Parameter(name: 'X-Tenant-ID', in: 'header', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Logged out successfully')]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, __('Logged out successfully'));
    }
}
