<?php

namespace Modules\MemberManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberEvaluation extends Model
{
    use SoftDeletes;
    protected $table = 'member_evaluations';

    protected $fillable = [
        'member_id',
        'evaluatable_type',
        'evaluatable_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * The member who submitted this evaluation.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the parent evaluatable model (SportSessionTemplate or Staff).
     */
    public function evaluatable(): MorphTo
    {
        return $this->morphTo();
    }
}
