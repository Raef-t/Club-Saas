<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreSafeRequest;
use Modules\Accounting\Http\Requests\UpdateSafeRequest;
use Modules\Accounting\Http\Resources\AccSafeResource;
use Modules\Accounting\Models\AccSafe;
use Modules\Accounting\Services\ReportService;
use Modules\Shared\Traits\SuccessResponseTrait;

class SafeController extends Controller
{
    use SuccessResponseTrait;

    public function __construct(protected ReportService $reportService) {}

    public function index(Request $request)
    {
        try {
            $safes = AccSafe::with('account')
                ->when($request->has('is_active'), fn($q) => $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)))
                ->orderBy('name')->get();
            return $this->successResponse(AccSafeResource::collection($safes), 'تم جلب الصناديق');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreSafeRequest $request)
    {
        try {
            $safe = AccSafe::create($request->validated());
            return $this->successResponse(new AccSafeResource($safe->load('account')), 'تم إضافة الصندوق بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $safe = AccSafe::with('account')->findOrFail($id);
            return $this->successResponse(new AccSafeResource($safe), 'تم جلب بيانات الصندوق');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function update(UpdateSafeRequest $request, $id)
    {
        try {
            $safe = AccSafe::findOrFail($id);
            $safe->update($request->validated());
            return $this->successResponse(new AccSafeResource($safe->load('account')), 'تم تحديث الصندوق بنجاح');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function statement(Request $request, $id)
    {
        try {
            $from = $request->get('from', now()->startOfMonth()->toDateString());
            $to   = $request->get('to', now()->toDateString());
            $data = $this->reportService->getSafeStatement((int) $id, $from, $to);
            return $this->successResponse($data, 'تم جلب كشف الصندوق');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
