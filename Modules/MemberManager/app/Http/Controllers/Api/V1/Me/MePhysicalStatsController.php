<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1\Me;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Http\Requests\Api\V1\Me\UpdatePhysicalStatsRequest;
use Modules\MemberManager\Services\Me\MePhysicalStatsService;
use OpenApi\Attributes as OA;

class MePhysicalStatsController extends BaseController
{
    protected $service;

    public function __construct(MePhysicalStatsService $service)
    {
        $this->service = $service;
    }

    // This controller and its route have been deprecated and removed.
}
