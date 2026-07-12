<?php

namespace Modules\ClubManager\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Locker extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'locker_number',
        // Availability status: available | with_member | with_staff | with_guest
        'status',
        // Polymorphic holder – who currently holds this key
        'holder_id',    // ID of the member/staff record (null for guests)
        'holder_type',  // 'member' | 'staff' | 'guest'
        'holder_name',  // cached display name or raw guest name
        'assigned_at',  // timestamp when the key was handed out
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
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