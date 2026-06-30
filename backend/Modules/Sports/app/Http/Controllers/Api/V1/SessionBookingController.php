<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Sports\Services\SessionService;
use Modules\Sports\Http\Resources\SportSessionBookingResource;
use Modules\Sports\Http\Requests\BookSessionRequest;
use Modules\Sports\Models\SportSessionBooking;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SessionBookingController extends BaseController
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    #[OA\Post(
        path: '/v1/sessions/{id}/book',
        summary: '🎟️ حجز جلسة للعضو',
        description: 'تسجيل حجز جديد لعضو في جلسة رياضية محددة.',
        tags: ['Session Bookings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الجلسة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم الحجز بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session booked successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 404, description: '🚫 الجلسة أو العضو غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function book(BookSessionRequest $request, int $id)
    {
        $booking = $this->sessionService->bookSession($id, $request->validated()['member_id']);
        return $this->successResponse(
            new SportSessionBookingResource($booking->load('session')),
            __('Session booked successfully'),
            201
        );
    }

    #[OA\Post(
        path: '/v1/bookings/{id}/cancel',
        summary: '❌ إلغاء حجز الجلسة',
        description: 'إلغاء حجز جلسة رياضية مسجل مسبقاً لعضو.',
        tags: ['Session Bookings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الحجز', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم إلغاء الحجز بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Booking cancelled successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الحجز غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function cancel(int $id)
    {
        $booking = $this->sessionService->cancelBooking($id);
        return $this->successResponse(
            new SportSessionBookingResource($booking->load('session')),
            __('Booking cancelled successfully')
        );
    }

    #[OA\Get(
        path: '/v1/bookings',
        summary: '📋 عرض قائمة الحجوزات',
        description: 'استرجاع جميع حجوزات الجلسات مع إمكانية الفلترة.',
        tags: ['Session Bookings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'member_id', in: 'query', required: false, description: 'معرف العضو', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'sports_session_id', in: 'query', required: false, description: 'معرف الجلسة', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'حالة الحجز (مثال: confirmed, cancelled)', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الحجوزات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Bookings retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $query = SportSessionBooking::with('session');

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->filled('sports_session_id')) {
            $query->where('sports_session_id', $request->input('sports_session_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $bookings = $query->latest()->paginate($request->input('per_page', 15));

        return $this->successResponse(
            SportSessionBookingResource::collection($bookings)->response()->getData(true),
            __('Bookings retrieved successfully')
        );
    }
}
