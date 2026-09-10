<?php

namespace Modules\AppVersions\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AppVersions\Models\AppVersion;
use App\Services\FirebaseService;

class AppVersionsController extends BaseController
{
    /**
     * Check if a newer app version is available.
     * Public Endpoint: GET /api/v1/app/check-version
     */
    public function checkVersion(Request $request)
    {
        // 1. Extract version and platform from headers or query parameters
        $clientVersion = $request->header('X-App-Version') ?? $request->query('version');
        $platform = $request->header('X-App-Platform') ?? $request->query('platform') ?? 'android';

        $platform = strtolower(trim($platform));
        if (!in_array($platform, ['android', 'ios'])) {
            $platform = 'android';
        }

        if (!$clientVersion) {
            return $this->errorResponse('رقم الإصدار مطلوب للتحقق من التحديثات (عبر ترويسة X-App-Version أو معامل version).', 400);
        }

        // 2. Fetch the latest active release for "تطبيق المتدرب" on the specified platform
        $latestRelease = AppVersion::active()
            ->forPlatform($platform)
            ->orderBy('build_number', 'desc')
            ->first();

        if (!$latestRelease) {
            return $this->successResponse([
                'has_update' => false,
                'is_force_update' => false,
                'app_name' => 'تطبيق المتدرب',
                'latest_version' => $clientVersion,
                'build_number' => null,
                'platform' => $platform,
                'download_url' => null,
                'file_size' => null,
                'formatted_file_size' => null,
                'release_notes' => 'التطبيق يعمل بأحدث نسخة متوفرة.',
            ], 'لا توجد إصدارات مسجلة حالياً.');
        }

        // 3. Compare client version with latest release version
        $hasUpdate = version_compare($clientVersion, $latestRelease->version_number, '<');

        if (!$hasUpdate) {
            return $this->successResponse([
                'has_update' => false,
                'is_force_update' => false,
                'app_name' => $latestRelease->app_name,
                'latest_version' => $latestRelease->version_number,
                'build_number' => $latestRelease->build_number,
                'platform' => $latestRelease->platform,
                'download_url' => $latestRelease->download_url,
                'file_size' => $latestRelease->file_size,
                'formatted_file_size' => $latestRelease->formatted_file_size,
                'release_notes' => $latestRelease->release_notes,
            ], 'التطبيق يعمل بأحدث إصدار.');
        }

        // 4. Check if any version between client version and latest version enforces an update
        $forcedVersions = AppVersion::active()
            ->forPlatform($platform)
            ->where('is_force_update', true)
            ->get();

        $isForceUpdate = false;
        foreach ($forcedVersions as $forced) {
            if (version_compare($forced->version_number, $clientVersion, '>')) {
                $isForceUpdate = true;
                break;
            }
        }

        return $this->successResponse([
            'has_update' => true,
            'is_force_update' => $isForceUpdate,
            'app_name' => $latestRelease->app_name,
            'latest_version' => $latestRelease->version_number,
            'build_number' => $latestRelease->build_number,
            'platform' => $latestRelease->platform,
            'download_url' => $latestRelease->download_url,
            'file_size' => $latestRelease->file_size,
            'formatted_file_size' => $latestRelease->formatted_file_size,
            'release_notes' => $latestRelease->release_notes,
        ], 'يوجد إصدار جديد متوفر لتطبيق المتدرب.');
    }

    /**
     * List all app versions (Admin Endpoint).
     */
    public function index(Request $request)
    {
        $query = AppVersion::query()->orderBy('build_number', 'desc');

        if ($request->filled('platform')) {
            $query->where('platform', strtolower(trim($request->platform)));
        }

        if ($request->has('is_active') && $request->is_active !== null && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('version_number', 'like', "%{$term}%")
                  ->orWhere('build_number', 'like', "%{$term}%")
                  ->orWhere('release_notes', 'like', "%{$term}%");
            });
        }

        if ($request->query('per_page') === 'all') {
            $versions = $query->get();
            return $this->successResponse($versions, 'تم استرجاع جميع إصدارات التطبيق بنجاح.');
        }

        $perPage = (int) $request->query('per_page', 15);
        $versions = $query->paginate($perPage);

        return $this->successResponse($versions, 'تم جلب قائمة إصدارات التطبيق بنجاح.');
    }

    /**
     * Store a new app version (Admin Endpoint).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'version_number' => 'required|string|max:50',
            'build_number'   => 'required|integer|min:1',
            'platform'       => 'required|in:android,ios',
            'download_url'   => 'nullable|url|required_without:app_file',
            'app_file'       => 'nullable|file|max:153600|required_without:download_url', // Up to 150MB
            'is_force_update'=> 'nullable',
            'release_notes'  => 'nullable|string',
            'is_active'      => 'nullable',
        ], [
            'version_number.required' => 'حقل رقم الإصدار مطلوب.',
            'build_number.required'   => 'حقل رقم البناء مطلوب.',
            'platform.required'       => 'يرجى تحديد نظام التشغيل (android أو ios).',
            'download_url.required_without' => 'يرجى إدخال رابط التحميل أو رفع ملف التطبيق.',
            'app_file.required_without'     => 'يرجى إدخال رابط التحميل أو رفع ملف التطبيق.',
            'app_file.max'            => 'الحد الأقصى لحجم ملف التطبيق هو 150 ميغابايت.',
        ]);

        $validated['app_name'] = 'تطبيق المتدرب';
        $validated['is_force_update'] = $request->boolean('is_force_update', false);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        // Handle app file upload
        if ($request->hasFile('app_file')) {
            $file = $request->file('app_file');
            $extension = $file->getClientOriginalExtension() ?: ($validated['platform'] === 'android' ? 'apk' : 'ipa');
            $cleanVersion = str_replace('.', '_', $validated['version_number']);
            $filename = 'trainee_' . $validated['platform'] . '_v' . $cleanVersion . '_' . time() . '.' . $extension;

            $path = $file->storeAs('apps', $filename, 'public');
            $validated['file_path'] = $path;
            $validated['file_size'] = $file->getSize();
            $validated['download_url'] = url('storage/' . $path);
        }

        $version = AppVersion::create($validated);

        // Broadcast FCM Update notification if version is active
        if ($version->is_active) {
            $this->broadcastFcmUpdate($version);
        }

        return $this->successResponse($version, 'تم تسجيل إصدار تطبيق المتدرب بنجاح.', 201);
    }

    /**
     * Show single app version.
     */
    public function show($id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return $this->errorResponse('الإصدار غير موجود.', 404);
        }

        return $this->successResponse($version, 'تم استرجاع بيانات الإصدار بنجاح.');
    }

    /**
     * Update an app version (Admin Endpoint).
     */
    public function update(Request $request, $id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return $this->errorResponse('الإصدار المطلوب غير موجود.', 404);
        }

        $validated = $request->validate([
            'version_number' => 'sometimes|required|string|max:50',
            'build_number'   => 'sometimes|required|integer|min:1',
            'platform'       => 'sometimes|required|in:android,ios',
            'download_url'   => 'nullable|url',
            'app_file'       => 'nullable|file|max:153600', // 150MB
            'is_force_update'=> 'nullable',
            'release_notes'  => 'nullable|string',
            'is_active'      => 'nullable',
        ]);

        $platform = $validated['platform'] ?? $version->platform;
        $versionNumber = $validated['version_number'] ?? $version->version_number;

        // Handle replacement app file upload
        if ($request->hasFile('app_file')) {
            $file = $request->file('app_file');
            $extension = $file->getClientOriginalExtension() ?: ($platform === 'android' ? 'apk' : 'ipa');
            $cleanVersion = str_replace('.', '_', $versionNumber);
            $filename = 'trainee_' . $platform . '_v' . $cleanVersion . '_' . time() . '.' . $extension;

            // Delete old file if present
            if ($version->file_path && Storage::disk('public')->exists($version->file_path)) {
                Storage::disk('public')->delete($version->file_path);
            }

            $path = $file->storeAs('apps', $filename, 'public');
            $validated['file_path'] = $path;
            $validated['file_size'] = $file->getSize();
            $validated['download_url'] = url('storage/' . $path);
        }

        if ($request->has('is_force_update')) {
            $validated['is_force_update'] = $request->boolean('is_force_update');
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $wasActive = $version->is_active;
        $version->update($validated);

        // Broadcast notification if it was not active before and became active now
        if ($version->is_active && !$wasActive) {
            $this->broadcastFcmUpdate($version);
        }

        return $this->successResponse($version, 'تم تحديث إصدار التطبيق بنجاح.');
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus($id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return $this->errorResponse('الإصدار غير موجود.', 404);
        }

        $version->is_active = !$version->is_active;
        $version->save();

        if ($version->is_active) {
            $this->broadcastFcmUpdate($version);
        }

        $statusLabel = $version->is_active ? 'مفعل' : 'معطل';
        return $this->successResponse($version, "تم تغيير حالة الإصدار إلى {$statusLabel}.");
    }

    /**
     * Delete an app version (Admin Endpoint).
     */
    public function destroy($id)
    {
        $version = AppVersion::find($id);
        if (!$version) {
            return $this->errorResponse('الإصدار غير موجود.', 404);
        }

        // Delete associated uploaded file if exists
        if ($version->file_path && Storage::disk('public')->exists($version->file_path)) {
            Storage::disk('public')->delete($version->file_path);
        }

        $version->delete();

        return $this->successResponse(null, 'تم حذف إصدار التطبيق بنجاح.');
    }

    /**
     * Broadcast Firebase FCM update notification for Trainee App.
     */
    protected function broadcastFcmUpdate(AppVersion $version): void
    {
        try {
            $firebaseService = app(FirebaseService::class);
            $topic = 'trainee_app_updates_' . $version->platform;
            $title = 'تحديث جديد متوفر لتطبيق المتدرب! 🚀';
            $body = 'تم إصدار النسخة ' . $version->version_number . '. يرجى التحديث للحصول على أحدث الميزات والتحسينات.';

            $firebaseService->sendToTopic($topic, $title, $body, [
                'action_type'    => 'open_screen',
                'screen_name'    => 'APP_UPDATE',
                'type'           => 'app_update',
                'app_name'       => 'تطبيق المتدرب',
                'latest_version' => (string) $version->version_number,
                'build_number'   => (string) $version->build_number,
                'platform'       => (string) $version->platform,
                'download_url'   => (string) $version->download_url,
                'is_force_update'=> $version->is_force_update ? '1' : '0',
            ]);

            Log::info('✅ تم إرسال إشعار تحديث تطبيق المتدرب بنجاح عبر FCM للموضوع: ' . $topic);
        } catch (\Throwable $e) {
            Log::warning('⚠️ تعذر إرسال إشعار FCM لتحديث تطبيق المتدرب: ' . $e->getMessage());
        }
    }
}
