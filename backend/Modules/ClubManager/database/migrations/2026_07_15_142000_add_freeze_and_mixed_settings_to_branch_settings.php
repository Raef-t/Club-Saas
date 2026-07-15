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
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->boolean('allow_freeze')->default(false)->after('locker_price');
            $table->boolean('display_mixed_activities')->default(false)->after('allow_freeze');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['allow_freeze', 'display_mixed_activities']);
        });
    }
};
