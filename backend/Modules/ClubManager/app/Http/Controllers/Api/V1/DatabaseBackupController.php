<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Services\DatabaseBackupService;
use Exception;
use OpenApi\Attributes as OA;

class DatabaseBackupController extends BaseController
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    #[OA\Get(
        path: '/v1/system/backup/download',
        summary: '📦 تحميل نسخة احتياطية من قاعدة البيانات',
        description: 'توليد ملف مضغوط ZIP يحتوي على نسخة احتياطية من قاعدة البيانات وتحميله.',
        tags: ['Database Backup'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إنشاء الملف وتنزيله بنجاح',
        content: new OA\MediaType(
            mediaType: 'application/zip'
        )
    )]
    #[OA\Response(
        response: 500,
        description: '❌ حدث خطأ أثناء التصدير',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'Failed to generate backup.')
            ]
        )
    )]
    public function download()
    {
        try {
            $backup = $this->backupService->generateBackupZip();

            return response()->download($backup['path'], $backup['filename'], [
                'Content-Type' => 'application/zip',
                'Access-Control-Expose-Headers' => 'Content-Disposition',
            ])->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return $this->errorResponse(__('Failed to generate database backup: :message', ['message' => $e->getMessage()]), 500);
        }
    }
}
