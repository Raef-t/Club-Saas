<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class CommandExecution extends Model
{
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
