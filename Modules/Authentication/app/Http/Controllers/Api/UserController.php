<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Http\Resources\UserResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class UserController extends BaseController
{
    #[OA\Post(
        path: '/v1/users/create-account',
        summary: '🔑 Create User Account for Existing Person',
        description: 'Provision a new authentication account for a previously registered person (Player, Coach, or Staff).',
        tags: ['User Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['person_id', 'username', 'password'],
            properties: [
                new OA\Property(property: 'person_id', type: 'integer', example: 1),
                new OA\Property(property: 'username', type: 'string', example: 'mohamed_gym'),
                new OA\Property(property: 'password', type: 'string', example: 'secure_password'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User account created successfully',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'string', example: 'success'),
            new OA\Property(property: 'data', ref: '#/components/schemas/User')
        ])
    )]
    public function createAccount(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'tenant_id' => session('tenant_id'),
            'person_id' => $request->person_id,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'is_active' => false, // Created as inactive by default
        ]);

        return $this->successResponse(
            new UserResource($user),
            __('User account created successfully for the selected person'),
            201
        );
    }

    #[OA\Post(
        path: '/v1/users/{id}/activate',
        summary: '✅ Activate User Account',
        tags: ['User Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'User activated')]
    public function activate($id)
    {
        $user = User::where('id', $id)->where('tenant_id', session('tenant_id'))->firstOrFail();
        $user->update(['is_active' => true]);

        return $this->successResponse(new UserResource($user), __('User account activated successfully'));
    }

    #[OA\Post(
        path: '/v1/users/{id}/deactivate',
        summary: '🚫 Deactivate User Account',
        tags: ['User Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'User deactivated')]
    public function deactivate($id)
    {
        $user = User::where('id', $id)->where('tenant_id', session('tenant_id'))->firstOrFail();
        $user->update(['is_active' => false]);

        return $this->successResponse(new UserResource($user), __('User account deactivated successfully'));
    }
}
