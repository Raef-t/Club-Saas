<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_types') && Schema::hasColumn('activity_types', 'is_private_equipment') && Schema::hasColumn('activity_types', 'has_shifts')) {
            DB::table('activity_types')
                ->where('is_private_equipment', true)
                ->where('has_shifts', true)
                ->update(['has_shifts' => false]);
        }
    }

    public function down(): void
    {
        // No reversal needed as private equipment activities cannot have shifts
    }
};
