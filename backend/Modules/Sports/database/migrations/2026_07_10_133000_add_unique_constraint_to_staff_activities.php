<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicates first to avoid constraint violation
        $duplicates = DB::table('staff_activities')
            ->select('staff_id', 'activity_id', DB::raw('MIN(id) as min_id'))
            ->groupBy('staff_id', 'activity_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('staff_activities')
                ->where('staff_id', $duplicate->staff_id)
                ->where('activity_id', $duplicate->activity_id)
                ->where('id', '!=', $duplicate->min_id)
                ->delete();
        }

        Schema::table('staff_activities', function (Blueprint $table) {
            $indexName = 'staff_activities_staff_id_activity_id_unique';
            $indexes = Schema::getIndexes('staff_activities');
            $indexExists = false;
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    $indexExists = true;
                    break;
                }
            }
            
            if (!$indexExists) {
                $table->unique(['staff_id', 'activity_id'], $indexName);
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_activities', function (Blueprint $table) {
            $indexName = 'staff_activities_staff_id_activity_id_unique';
            $indexes = Schema::getIndexes('staff_activities');
            $indexExists = false;
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    $indexExists = true;
                    break;
                }
            }
            
            if ($indexExists) {
                $table->dropUnique($indexName);
            }
        });
    }
};
