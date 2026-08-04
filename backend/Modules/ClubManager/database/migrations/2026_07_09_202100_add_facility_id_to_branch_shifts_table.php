<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branch_shifts', function (Blueprint $table) {
            $table->unsignedBigInteger('facility_id')->nullable()->after('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_shifts', function (Blueprint $table) {
            $table->dropColumn('facility_id');
        });
    }
};
