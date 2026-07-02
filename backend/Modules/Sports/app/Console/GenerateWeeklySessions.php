<?php

namespace Modules\Sports\Console;

use Illuminate\Console\Command;
use Modules\Sports\Services\ScheduleGeneratorService;
use Carbon\Carbon;

class GenerateWeeklySessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sports:generate-sessions {--weeks=1 : Number of weeks to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sports sessions from templates for upcoming weeks';

    /**
     * Execute the console command.
     */
    public function handle(ScheduleGeneratorService $generatorService)
    {
        $weeks = (int) $this->option('weeks');
        
        $startDate = now()->startOfWeek();
        $endDate = now()->addWeeks($weeks - 1)->endOfWeek();

        $this->info("Generating sessions from {$startDate->toDateString()} to {$endDate->toDateString()}...");

        try {
            $count = $generatorService->generateSessions($startDate->toDateString(), $endDate->toDateString());
            $this->info("Successfully generated $count sessions.");
        } catch (\Exception $e) {
            $this->error("Error generating sessions: " . $e->getMessage());
        }
    }
}
