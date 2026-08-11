<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommandExecution extends Model
{
    use SoftDeletes;
    protected $table = 'command_executions';

    protected $fillable = [
        'command_signature',
        'period',
        'executed_at',
        'status',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];
}
