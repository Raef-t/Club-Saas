<?php

namespace Modules\StaffManager\Models;

use Illuminate\Database\Eloquent\Model;

class CoachCertification extends Model
{
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
