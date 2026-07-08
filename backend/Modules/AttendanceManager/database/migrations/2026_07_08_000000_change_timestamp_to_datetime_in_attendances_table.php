<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change columns to DATETIME to drop the implicit ON UPDATE CURRENT_TIMESTAMP behavior in MySQL
        DB::statement('ALTER TABLE attendances MODIFY check_in_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE attendances MODIFY check_out_at DATETIME NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE attendances MODIFY check_in_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE attendances MODIFY check_out_at TIMESTAMP NULL');
    }
};
