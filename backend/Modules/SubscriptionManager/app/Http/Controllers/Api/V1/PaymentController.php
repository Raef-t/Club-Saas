<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Models\Payment;
use Modules\SubscriptionManager\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaymentController extends BaseController
{
    #[OA\Get(
        path: '/v1/payments',
        summary: '💰 عرض جميع الدفعات المالية',
        description: 'استرجاع قائمة بجميع الدفعات المالية المسجلة في النظام.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الدفعات بنجاح')]
    public function index(Request $request)
    {
        $query = Payment::orderBy('id', 'desc');

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $payments = $query->paginate($perPage);
        } else {
            $payments = $query->get();
        }

        return $this->successResponse(PaymentResource::collection($payments), __('Payments retrieved successfully'));
    }

    #[OA\Get(
        path: '/v1/payments/{id}',
        summary: '🔍 عرض تفاصيل دفعة مالية',
        description: 'عرض تفاصيل دفعة مالية معينة.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الدفعة', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الدفعة بنجاح')]
    #[OA\Response(response: 404, description: '🚫 الدفعة غير موجودة')]
    public function show($id)
    {
        $payment = Payment::findOrFail($id);
        return $this->successResponse(new PaymentResource($payment), __('Payment retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/payments/{id}',
        summary: '✏️ تحديث قيمة أو طريقة دفعة مالية',
        description: 'تعديل قيمة الدفعة المالية أو طريقة الدفع. يتم تلقائياً تحديث إجمالي المدفوع والمتبقي للاشتراك والفاتورة المرتبطة.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الدفعة', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'سبب التعديل (حقل إجباري قبل الحفظ)', example: 'تصحيح المبلغ المدفوع وتعديل طريقة الدفع'),
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100.00),
                new OA\Property(property: 'payment_method', type: 'string', example: 'cash'),
                new OA\Property(property: 'receipt_number', type: 'string', example: 'REC-2026-001', description: 'رقم إيصال الدفع (اختياري)')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تحديث الدفعة بنجاح')]
    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        
        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|string',
            'receipt_number' => 'sometimes|nullable|string|max:100',
        ]);

        $payment->update($data);

        return $this->successResponse(new PaymentResource($payment), __('Payment updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/payments/{id}',
        summary: '🗑️ حذف دفعة مالية (Soft Delete)',
        description: 'حذف دفعة مالية وتخفيض قيمة المدفوعات للاشتراك المرتبط بها. يتطلب إرسال كلمة التأكيد "delete".',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الدفعة', schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', description: 'تأكيد الحذف (delete)', example: '')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم حذف الدفعة بنجاح')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"')]
    public function destroy(Request $request, $id)
    {
        $confirm = strtolower(trim($request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? ''));

        if ($confirm !== 'delete') {
            return $this->errorResponse(
                __('سيتم حذف هذه الدفعة وتحديث إجمالي المدفوعات والتبقي للاشتراك المرتبط، هل أنت متأكد؟ أرسل "delete" للتأكيد.'),
                422
            );
        }

        $payment = Payment::findOrFail($id);
        $payment->delete();

        return $this->successResponse(null, __('Payment deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/payments/trashed',
        summary: '🗑️ عرض الدفعات المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالدفعات المالية المحذوفة.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم جلب الدفعات المحذوفة بنجاح')]
    public function trashed(Request $request)
    {
        $query = Payment::onlyTrashed();

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $payments = $query->paginate($perPage);
        } else {
            $payments = $query->get();
        }

        return $this->successResponse(PaymentResource::collection($payments), __('Trashed payments retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/payments/{id}/restore',
        summary: '♻️ استرجاع دفعة مالية محذوفة',
        description: 'استرجاع الدفعة وإعادة إضافة قيمتها للمدفوعات.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الدفعة', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الدفعة بنجاح')]
    public function restore($id)
    {
        $payment = Payment::onlyTrashed()->findOrFail($id);
        $payment->restore();

        return $this->successResponse(new PaymentResource($payment), __('Payment restored successfully'));
    }
}
