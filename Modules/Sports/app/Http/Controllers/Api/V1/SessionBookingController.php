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
        summary: '🎟️ Book a session for a member',
        tags: ['Session Bookings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Session booked successfully')
        ]
    )]
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
        summary: '❌ Cancel a session booking',
        tags: ['Session Bookings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Booking cancelled successfully')
        ]
    )]
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
        summary: '📋 List bookings with optional filters',
        tags: ['Session Bookings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Bookings retrieved successfully')
        ]
    )]
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
