<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\MemberManager\Models\Member;
use Modules\MemberManager\Models\PlayerUnavailability;
use Modules\MemberManager\Http\Requests\StorePlayerUnavailabilityRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class PlayerUnavailabilityController extends BaseController
{
    #[OA\Get(
        path: '/v1/members/{member}/unavailabilities',
        summary: '🗓️ عرض أوقات عدم التفرغ للعضو',
        description: 'استرجاع قائمة بأوقات عدم التفرغ (الإجازات، الغياب، الإصابات) الخاصة بعضو محدد.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'member', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع البيانات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player unavailabilities retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2023-10-10'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2023-10-15'),
                    new OA\Property(property: 'reason', type: 'string', example: 'إجازة مرضية')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    public function index(Member $member)
    {
        $unavailabilities = $member->unavailabilities()->get();
        return $this->successResponse($unavailabilities, __('Player unavailabilities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/members/{member}/unavailabilities',
        summary: '➕ إضافة وقت عدم تفرغ جديد',
        description: 'إضافة فترة زمنية يكون فيها العضو غير متفرغ أو غائبًا.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'member', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['start_date', 'end_date', 'reason'],
            properties: [
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2023-10-10'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2023-10-15'),
                new OA\Property(property: 'reason', type: 'string', example: 'إجازة مرضية')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الإضافة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player unavailability created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Member not found.')]))]
    public function store(StorePlayerUnavailabilityRequest $request, Member $member)
    {
        $unavailability = $member->unavailabilities()->create($request->validated());
        return $this->successResponse($unavailability, __('Player unavailability created successfully'), 201);
    }

    #[OA\Delete(
        path: '/v1/members/{member}/unavailabilities/{unavailability}',
        summary: '🗑️ حذف وقت عدم التفرغ',
        description: 'حذف فترة عدم تفرغ سابقة خاصة بالعضو.',
        tags: ['Member Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'member', in: 'path', required: true, description: 'معرف العضو', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'unavailability', in: 'path', required: true, description: 'معرف فترة عدم التفرغ', schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Player unavailability deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الفترة لا تنتمي لهذا العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Unavailability does not belong to this member')]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الفترة أو العضو', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(Member $member, PlayerUnavailability $unavailability)
    {
        if ($unavailability->member_id !== $member->id) {
            return $this->errorResponse(__('Unavailability does not belong to this member'), 403);
        }

        $unavailability->delete();
        return $this->successResponse(null, __('Player unavailability deleted successfully'));
    }
}
