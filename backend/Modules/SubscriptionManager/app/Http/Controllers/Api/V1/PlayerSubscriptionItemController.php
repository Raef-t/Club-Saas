<?php
namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Services\PlayerSubscriptionItemService;
use Modules\SubscriptionManager\Http\Requests\StorePlayerSubscriptionItemRequest;
use Modules\SubscriptionManager\Http\Requests\UpdatePlayerSubscriptionItemRequest;
use Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionItemResource;
use OpenApi\Attributes as OA;

class PlayerSubscriptionItemController extends BaseController
{
    protected $service;

    public function __construct(PlayerSubscriptionItemService $service) {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/player-subscription-items',
        summary: '📦 عرض عناصر اشتراكات اللاعبين',
        tags: ['Player Subscriptions'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200, 
        description: '✅ تم الاسترجاع بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ],
            example: [
                'status' => 'success',
                'message' => 'Retrieved successfully',
                'data' => [
                    [
                        'id' => 1,
                        'player_subscription_id' => 1,
                        'activity_id' => 2,
                        'total_sessions' => 12,
                        'remaining_sessions' => 10,
                        'is_unlimited' => false
                    ]
                ]
            ]
        )
    )]
    public function index(\Illuminate\Http\Request $request) {
        return $this->successResponse(PlayerSubscriptionItemResource::collection($this->service->getAll($request->all())), 'Retrieved successfully');
    }

    public function store(StorePlayerSubscriptionItemRequest $request) {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new PlayerSubscriptionItemResource($record), 'Created successfully', 201);
    }

    public function show($id) {
        return $this->successResponse(new PlayerSubscriptionItemResource($this->service->getById($id)), 'Retrieved successfully');
    }

    public function update(UpdatePlayerSubscriptionItemRequest $request, $id) {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new PlayerSubscriptionItemResource($record), 'Updated successfully');
    }

    public function destroy($id) {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
