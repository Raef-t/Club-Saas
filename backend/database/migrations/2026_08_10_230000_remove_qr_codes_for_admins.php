<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('person_qr_codes')) {
            // Delete QR codes for person_id = 1
            DB::table('person_qr_codes')->where('person_id', 1)->delete();

            // Delete QR codes for any person with type = 'admin'
            $adminPersonIds = DB::table('people')->where('type', 'admin')->pluck('id');
            if ($adminPersonIds->isNotEmpty()) {
                DB::table('person_qr_codes')
                    ->whereIn('person_id', $adminPersonIds)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed
    }
};
