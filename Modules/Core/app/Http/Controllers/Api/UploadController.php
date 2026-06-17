<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class UploadController extends BaseController
{
    #[OA\Post(
        path: '/v1/upload',
        summary: '📤 رفع الملفات',
        description: 'رفع ملف (صورة، شهادة، أو أي مرفق) إلى الخادم وإرجاع الرابط العام الخاص به.',
        tags: ['Core Utilities'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'الملف المراد رفعه (الحد الأقصى 5 ميجابايت، الأنواع المدعومة: jpeg, png, jpg, gif, pdf)',
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'الملف المراد رفعه')
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم رفع الملف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم رفع الملف بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'path', type: 'string', example: 'uploads/abc123xyz.jpg'),
                        new OA\Property(property: 'url', type: 'string', example: 'https://club-saas.com/storage/uploads/abc123xyz.jpg')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: '❌ فشل رفع الملف أو الملف غير صالح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'فشل في رفع الملف')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح (Unauthenticated)',
        content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')])
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة الملف',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'file', type: 'array', items: new OA\Items(type: 'string', example: 'يجب أن يكون الملف من نوع: jpeg, png, jpg, gif, pdf وحجمه لا يتجاوز 5 ميجابايت.'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:5120', // Max 5MB
        ]);

        if ($request->file('file')->isValid()) {
            $file = $request->file('file');
            
            // Store file under public disk in 'uploads' directory
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');
            $path = $disk->put('uploads', $file);
            
            // Generate public URL
            $url = $disk->url($path);

            return $this->successResponse([
                'path' => $path,
                'url' => $url,
            ], __('File uploaded successfully'), 201);
        }

        return $this->errorResponse(__('Invalid file upload'), 400);
    }
}
