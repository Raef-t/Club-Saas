<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class UserController extends BaseController
{
    #[OA\Get(
        path: '/v1/users',
        summary: '📋 عرض قائمة المستخدمين',
        description: 'يسمح للإدارة بعرض جميع حسابات المستخدمين المسجلة في النادي مع حالاتهم (نشط/غير نشط).',
        tags: ['User Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'X-Tenant-ID', in: 'header', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'Success')]
    public function index()
    {
        $users = User::with('person')->get();
        return $this->successResponse($users);
    }

    #[OA\Patch(
        path: '/v1/users/{id}/activate',
        summary: '✅ تفعيل حساب مستخدم (Activate)',
        description: "يستخدم لتحويل حالة الحساب إلى **نشط**، مما يسمح للمستخدم بتسجيل الدخول للنظام.",
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ]
    )]
    #[OA\Parameter(name: 'X-Tenant-ID', in: 'header', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'User activated successfully')]
    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true]);

        return $this->successResponse(null, __('User account activated successfully'));
    }

    #[OA\Patch(
        path: '/v1/users/{id}/deactivate',
        summary: '🚫 إيقاف حساب مستخدم (Deactivate)',
        description: "يستخدم لتحويل حالة الحساب إلى **غير نشط**، مما يمنع المستخدم من الدخول للنظام فوراً.",
        tags: ['User Management'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ]
    )]
    #[OA\Parameter(name: 'X-Tenant-ID', in: 'header', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: 'User deactivated successfully')]
    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => false]);

        return $this->successResponse(null, __('User account deactivated successfully'));
    }
}
