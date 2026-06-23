<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Http\Requests\StorePeriodRequest;
use Modules\Accounting\Http\Resources\AccPeriodResource;
use Modules\Accounting\Models\AccPeriod;
use Modules\Accounting\Services\PeriodService;
use Modules\Shared\Traits\SuccessResponseTrait;

class PeriodController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected PeriodService $periodService) {}

    public function index()
    {
        try {
            $periods = AccPeriod::orderBy('start_date', 'desc')->get();
            return $this->successResponse(AccPeriodResource::collection($periods), 'تم جلب الفترات المحاسبية بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StorePeriodRequest $request)
    {
        try {
            $period = AccPeriod::create($request->validated());
            return $this->successResponse(new AccPeriodResource($period), 'تم إنشاء الفترة المحاسبية بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            return $this->successResponse(new AccPeriodResource($period), 'تم جلب بيانات الفترة');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function close($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            $period = $this->periodService->closePeriod($period, Auth::id());
            return $this->successResponse(new AccPeriodResource($period), 'تم إغلاق الفترة المحاسبية بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function lock($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            $period = $this->periodService->lockPeriod($period, Auth::id());
            return $this->successResponse(new AccPeriodResource($period), 'تم قفل الفترة نهائياً');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function reopen($id)
    {
        try {
            $period = AccPeriod::findOrFail($id);
            $period = $this->periodService->reopenPeriod($period);
            return $this->successResponse(new AccPeriodResource($period), 'تم إعادة فتح الفترة بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
