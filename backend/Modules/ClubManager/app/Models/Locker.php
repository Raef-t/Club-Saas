<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class Locker extends Model
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = ['reservations'];

    public function reservations()
    {
        return $this->hasMany(\Modules\SubscriptionManager\Models\LockerReservation::class, 'locker_id');
    }

    protected $fillable = [
        'branch_id',
        'locker_number',
        'key_number',
        'status',
    ];


    // ──────────────────────────────────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function isWithGuest(): bool
    {
        return $this->status === 'with_guest';
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

    public function scopeWithGuest($query)
    {
        return $query->where('status', 'with_guest');
    }
}