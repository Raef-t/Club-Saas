<?php

namespace Modules\Core\Traits;

use Modules\Core\Models\CommandExecution;
use Illuminate\Support\Facades\Log;

trait TracksCommandExecution
{
    /**
     * Check if the command has already been executed for the given period.
     *
     * @param string $period
     * @return bool
     */
    protected function hasExecutedForPeriod(string $period): bool
    {
        $signature = $this->signature ?? $this->getName();
        // Remove command arguments/options from signature if present
        $cleanSignature = explode(' ', $signature)[0];

        return CommandExecution::where('command_signature', $cleanSignature)
            ->where('period', $period)
            ->where('status', 'success')
            ->exists();
    }

    /**
     * Mark the command as executed for the given period.
     *
     * @param string $period
     * @return void
     */
    protected function markAsExecuted(string $period): void
    {
        $signature = $this->signature ?? $this->getName();
        $cleanSignature = explode(' ', $signature)[0];

        CommandExecution::updateOrCreate(
            [
                'command_signature' => $cleanSignature,
                'period' => $period,
            ],
            [
                'executed_at' => now(),
                'status' => 'success',
            ]
        );
        
        Log::info("Command {$cleanSignature} executed successfully for period {$period}");
    }
}
