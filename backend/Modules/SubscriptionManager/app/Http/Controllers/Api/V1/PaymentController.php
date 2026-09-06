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
        description: 'استرجاع قائمة بجميع الدفعات المالية المسجلة في النظام مع إمكانية الترقيم الصفحات.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في كل صفحة (أو "all" لجلب كل السجلات بدون ترقيم صفحات)',
        schema: new OA\Schema(type: 'string', example: '15')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة المستهدفة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة الدفعات بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payments retrieved successfully',
                'data' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'id' => 1,
                            'receipt_number' => 'REC-2026-001',
                            'invoice_id' => 5,
                            'safe_id' => 2,
                            'amount' => 500.00,
                            'payment_method' => 'cash',
                            'status' => 'completed',
                            'reason' => 'دفعة أولى عند الاشتراك',
                            'created_at' => '2026-06-30 14:20:00',
                            'updated_at' => '2026-06-30 14:20:00',
                            'deleted_at' => null
                        ]
                    ],
                    'first_page_url' => 'http://localhost:8000/api/v1/payments?page=1',
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => 'http://localhost:8000/api/v1/payments?page=1',
                    'next_page_url' => null,
                    'path' => 'http://localhost:8000/api/v1/payments',
                    'per_page' => 15,
                    'prev_page_url' => null,
                    'to' => 1,
                    'total' => 1
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح - رمز المرور مفقود أو غير صالح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
        description: 'استرجاع تفاصيل دفعة مالية معينة عن طريق معرف الدفعة الرقمي.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للدفعة المالية',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل الدفعة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payment retrieved successfully',
                'data' => [
                    'id' => 1,
                    'receipt_number' => 'REC-2026-001',
                    'invoice_id' => 5,
                    'safe_id' => 2,
                    'amount' => 500.00,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'reason' => 'دفعة أولى عند الاشتراك',
                    'created_at' => '2026-06-30 14:20:00',
                    'updated_at' => '2026-06-30 14:20:00',
                    'deleted_at' => null
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الدفعة المالية غير موجودة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function show($id)
    {
        $payment = Payment::findOrFail($id);
        return $this->successResponse(new PaymentResource($payment), __('Payment retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/payments/{id}',
        summary: '✏️ تحديث قيمة أو طريقة دفعة مالية',
        description: 'تعديل قيمة الدفعة المالية أو طريقة الدفع ورقم الإيصال مع إجبار إرسال حقل reason لحفظ سببيات التعديل، ويتم تلقائياً إثر التعديل تحديث إجمالي المدفوع والمتبقي للاشتراك والفاتورة المرتبطة.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للدفعة المراد تعديلها',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات التعديل المقترحة للدفعة',
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'تصحيح المبلغ المدفوع وتعديل طريقة الدفع إلى نقدي', description: 'سبب التعديل (حقل إجباري قبل الحفظ)'),
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 600.00, description: 'المبلغ الجديد (أدنى قيمة 0.01)'),
                new OA\Property(property: 'payment_method', type: 'string', example: 'cash', description: 'طريقة الدفع (cash, card, bank_transfer)'),
                new OA\Property(property: 'receipt_number', type: 'string', nullable: true, example: 'REC-2026-001', description: 'رقم إيصال الدفع (اختياري)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الدفعة وإعادة حساب أرصدة الفاتورة والاشتراك بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payment updated successfully',
                'data' => [
                    'id' => 1,
                    'receipt_number' => 'REC-2026-001',
                    'invoice_id' => 5,
                    'safe_id' => 2,
                    'amount' => 600.00,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'reason' => 'تصحيح المبلغ المدفوع وتعديل طريقة الدفع إلى نقدي',
                    'created_at' => '2026-06-30 14:20:00',
                    'updated_at' => '2026-06-30 15:00:00',
                    'deleted_at' => null
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من البيانات (مثال: عدم إرسال سبب التعديل reason)',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'reason' => ['The reason field is required.']
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الدفعة المالية غير موجودة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
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
        description: 'حذف دفعة مالية وتخفيض قيمة المدفوعات للاشتراك المرتبط بها. يتطلب إرسال كلمة التأكيد "delete" في كائن الطلب.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للدفعة المراد حذفها',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: false,
        description: 'كلمة تأكيد الحذف الإجبارية',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirm', type: 'string', example: 'delete', description: 'تأكيد الحذف (يجب أن تكون قيمتها بالضبط "delete")')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الدفعة وتخفيض مدفوعات الاشتراك المرتبط بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payment deleted successfully',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'سيتم حذف هذه الدفعة وتحديث إجمالي المدفوعات والتبقي للاشتراك المرتبط، هل أنت متأكد؟ أرسل "delete" للتأكيد.'
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الدفعة غير موجودة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
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
        description: 'جلب قائمة بالدفعات المالية المحذوفة مؤقتاً مع إمكانية الترقيم الصفحات.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في الصفحة (أو اكتب "all" لجلب كافة الدفعات المحذوفة بدون ترقيم)',
        schema: new OA\Schema(type: 'string', example: '15')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة المستهدفة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الدفعات المحذوفة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Trashed payments retrieved successfully',
                'data' => [
                    [
                        'id' => 1,
                        'receipt_number' => 'REC-2026-001',
                        'invoice_id' => 5,
                        'safe_id' => 2,
                        'amount' => 500.00,
                        'payment_method' => 'cash',
                        'status' => 'completed',
                        'reason' => 'دفعة ملغاة',
                        'created_at' => '2026-06-30 14:20:00',
                        'updated_at' => '2026-06-30 15:30:00',
                        'deleted_at' => '2026-06-30 15:30:00'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
        description: 'استرجاع الدفعة المالية من سلة المهملات وإعادة إضافة قيمتها للمدفوعات في الفاتورة والاشتراك.',
        tags: ['Invoices & Payments'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للدفعة المراد استرجاعها',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الدفعة المالية بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Payment restored successfully',
                'data' => [
                    'id' => 1,
                    'receipt_number' => 'REC-2026-001',
                    'invoice_id' => 5,
                    'safe_id' => 2,
                    'amount' => 500.00,
                    'payment_method' => 'cash',
                    'status' => 'completed',
                    'reason' => 'استرجاع دفعة مالية',
                    'created_at' => '2026-06-30 14:20:00',
                    'updated_at' => '2026-06-30 16:00:00',
                    'deleted_at' => null
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الدفعة غير موجودة في سلة المهملات',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    public function restore($id)
    {
        $payment = Payment::onlyTrashed()->findOrFail($id);
        $payment->restore();

        return $this->successResponse(new PaymentResource($payment), __('Payment restored successfully'));
    }
}
