<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoachCertification extends Model
{
    use SoftDeletes;
    protected $table = 'coach_certifications';

    protected $fillable = [
        'coach_detail_id',
        'name',
        'issuer',
        'issue_date',
        'expiry_date',
        'document_url',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * The coach detail this certification belongs to.
     */
    public function coachDetail()
    {
        return $this->belongsTo(CoachDetail::class);
    }

    /**
     * Check if the certification has expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
