<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Locker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'locker_number',
        'key_number',
        'status',
        'reason',
    ];


    // ──────────────────────────────────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reservations()
    {
        return $this->hasMany(\Modules\SubscriptionManager\Models\LockerReservation::class, 'locker_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleted(function ($locker) {
            $locker->reservations()->delete();
        });

        static::restored(function ($locker) {
            $locker->reservations()->onlyTrashed()->restore();
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Status helper methods
    // ──────────────────────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isWithMember(): bool
    {
        return $this->status === 'with_member';
    }

    public function isWithStaff(): bool
    {
        return $this->status === 'with_staff';
    }

    public function isWithCoach(): bool
    {
        return $this->status === 'with_coach';
    }

    public function isMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    public function isOccupied(): bool
    {
        return $this->status !== 'available';
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Query Scopes
    // ──────────────────────────────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', '!=', 'available');
    }

    public function scopeWithMember($query)
    {
        return $query->where('status', 'with_member');
    }

    public function scopeWithStaff($query)
    {
        return $query->where('status', 'with_staff');
    }

    public function scopeWithCoach($query)
    {
        return $query->where('status', 'with_coach');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }
}