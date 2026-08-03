<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GateDevice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id',
        'branch_id',
        'name',
        'mac_address',
        'api_token',
        'is_active',
    ];

    protected $hidden = [
        'api_token',
    ];

    /**
     * Generate a new unique API token for this gate device.
     */
    public function generateToken(): string
    {
        $token = Str::random(60);
        $this->api_token = hash('sha256', $token);
        $this->save();

        return $token;
    }
}
