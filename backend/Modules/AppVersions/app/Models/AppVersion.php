<?php

namespace Modules\AppVersions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasCreatedBy;

class AppVersion extends Model
{
    use SoftDeletes, HasCreatedBy;

    protected $table = 'app_versions';

    protected $fillable = [
        'app_name',
        'version_number',
        'build_number',
        'platform',
        'download_url',
        'file_path',
        'file_size',
        'is_force_update',
        'release_notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'build_number' => 'integer',
        'file_size' => 'integer',
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formatted_file_size',
    ];

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->file_size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Scope for active versions only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', strtolower(trim($platform)));
    }
}
