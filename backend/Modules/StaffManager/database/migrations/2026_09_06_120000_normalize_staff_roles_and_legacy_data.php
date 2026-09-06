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
        // Normalize legacy 'receptionist' role to 'reception'
        DB::table('staff')->where('role', 'receptionist')->update(['role' => 'reception']);
        DB::table('authentication_users')->where('role', 'receptionist')->update(['role' => 'reception']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as 'reception' is the standard role
    }
};
