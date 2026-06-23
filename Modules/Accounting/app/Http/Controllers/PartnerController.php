<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StorePartnerRequest;
use Modules\Accounting\Http\Requests\UpdatePartnerRequest;
use Modules\Accounting\Http\Resources\AccPartnerResource;
use Modules\Accounting\Models\AccPartner;
use Modules\Accounting\Services\ReportService;
use Modules\Shared\Traits\SuccessResponseTrait;

class PartnerController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected ReportService $reportService) {}

    public function index()
    {
        try {
            $partners = AccPartner::with('capitalAccount', 'drawingsAccount')->orderBy('name')->get();
            return $this->successResponse(AccPartnerResource::collection($partners), 'تم جلب الشركاء');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StorePartnerRequest $request)
    {
        try {
            $partner = AccPartner::create($request->validated());
            return $this->successResponse(new AccPartnerResource($partner->load('capitalAccount')), 'تم إضافة الشريك بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $partner = AccPartner::with('capitalAccount', 'drawingsAccount')->findOrFail($id);
            return $this->successResponse(new AccPartnerResource($partner), 'تم جلب بيانات الشريك');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function update(UpdatePartnerRequest $request, $id)
    {
        try {
            $partner = AccPartner::findOrFail($id);
            $partner->update($request->validated());
            return $this->successResponse(new AccPartnerResource($partner->load('capitalAccount')), 'تم تحديث بيانات الشريك');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function statement(Request $request, $id)
    {
        try {
            $periodId = $request->get('period_id');
            if (!$periodId) return $this->error('معرف الفترة مطلوب', 422);
            $data = $this->reportService->getPartnerStatement((int) $id, (int) $periodId);
            return $this->successResponse($data, 'تم جلب كشف حساب الشريك');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
