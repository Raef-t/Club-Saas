<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Authentication\Http\Requests\ChangePasswordRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Modules\Authentication\Http\Requests\LoginRequest;

class AuthController extends BaseController
{
    #[OA\Post(
        path: '/v1/auth/login',
        summary: '🔐 User Login & Token Generation',
        description: 'Authenticate a user and return a Bearer Token.',
        tags: ['Authentication']
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
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('username', $validated['username'])->first();

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

    #[OA\Get(
        path: '/v1/auth/me',
        summary: '👤 Get Authenticated User Profile',
        description: 'Returns the currently authenticated user with their associated profiles (Player/Staff).',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ Successfully Retrieved Profile',
        content: new OA\JsonContent(ref: '#/components/schemas/ApiResponse')
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function me(Request $request)
    {
        $user = clone $request->user();
        $user->load('person');

        $personData = $user->person;
        $profileData = [
            'id' => $user->id,
            'username' => $user->username,
            'is_active' => $user->is_active,
            'person' => $personData,
        ];

        // Enrich with member-specific data if the user is a player
        if ($personData) {
            $member = DB::table('members')->where('person_id', $personData->id)->first();

            if ($member) {
                $latestMeasurement = DB::table('member_measurements')
                    ->where('member_id', $member->id)
                    ->orderByDesc('measurement_date')
                    ->first();

                $profileData['member'] = [
                    'id' => $member->id,
                    'member_number' => $member->member_number,
                    'membership_status' => $member->membership_status,
                    'is_vip' => $this->checkIsVip($member->id),
                ];

                $profileData['measurements'] = $latestMeasurement ? [
                    'weight' => (float) $latestMeasurement->weight,
                    'height' => $latestMeasurement->height ? (float) $latestMeasurement->height : null,
                    'bmi' => $latestMeasurement->bmi ? (float) $latestMeasurement->bmi : null,
                    'measured_at' => $latestMeasurement->measurement_date,
                ] : null;

                // Age from person dob
                $profileData['age'] = $personData->dob
                    ? \Carbon\Carbon::parse($personData->dob)->age
                    : null;

                // Health status from chronic_diseases field
                $profileData['health_status'] = $personData->chronic_diseases ?: null;
            }
        }

        return $this->successResponse($profileData, __('Profile retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/auth/change-password',
        summary: '🔑 Change Password',
        description: 'Change the authenticated user\'s password.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['current_password', 'new_password', 'new_password_confirmation'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string'),
                new OA\Property(property: 'new_password', type: 'string'),
                new OA\Property(property: 'new_password_confirmation', type: 'string'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Password changed successfully')]
    #[OA\Response(response: 422, description: 'Validation error or incorrect current password')]
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse(__('Current password is incorrect'), 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return $this->successResponse(null, __('Password changed successfully'));
    }

    /**
     * Check if member has an active VIP subscription.
     */
    protected function checkIsVip(int $memberId): bool
    {
        return DB::table('player_subscriptions')
            ->join('subscription_plans', 'player_subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('player_subscriptions.member_id', $memberId)
            ->where('player_subscriptions.status', 'active')
            ->where('subscription_plans.type', 'like', '%vip%')
            ->exists();
    }
}
