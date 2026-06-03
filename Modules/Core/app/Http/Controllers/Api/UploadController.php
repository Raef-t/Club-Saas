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
        summary: '📤 Upload file (Photo, Certificate, background image)',
        description: 'Uploads a file to the public disk and returns its URL.',
        tags: ['Core Utilities'],
        security: [['bearerAuth' => []]]
    )]
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
