<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Http\Requests\StoreCounterpartyRequest;
use Modules\Accounting\Http\Requests\UpdateCounterpartyRequest;
use Modules\Accounting\Http\Resources\AccCounterpartyResource;
use Modules\Accounting\Models\AccCounterparty;
use Modules\Shared\Traits\SuccessResponseTrait;

class CounterpartyController extends Controller
{
    use SuccessResponseTrait;

    public function index(Request $request)
    {
        try {
            $query = AccCounterparty::query();
            if ($request->has('type'))         $query->where('type', $request->type);
            if ($request->has('reference_type') && $request->has('reference_id')) {
                $query->byReference($request->reference_type, $request->reference_id);
            }
            $counterparties = $query->orderBy('name')->get();
            return $this->successResponse(AccCounterpartyResource::collection($counterparties), 'تم جلب الأطراف');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(StoreCounterpartyRequest $request)
    {
        try {
            $cp = AccCounterparty::create($request->validated());
            return $this->successResponse(new AccCounterpartyResource($cp), 'تم إضافة الطرف بنجاح', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $cp = AccCounterparty::findOrFail($id);
            return $this->successResponse(new AccCounterpartyResource($cp), 'تم جلب بيانات الطرف');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function update(UpdateCounterpartyRequest $request, $id)
    {
        try {
            $cp = AccCounterparty::findOrFail($id);
            $cp->update($request->validated());
            return $this->successResponse(new AccCounterpartyResource($cp), 'تم تحديث بيانات الطرف');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
