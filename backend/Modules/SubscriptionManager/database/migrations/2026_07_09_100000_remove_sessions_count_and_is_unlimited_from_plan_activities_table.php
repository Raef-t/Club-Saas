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
        Schema::table('plan_activities', function (Blueprint $table) {
            $table->dropColumn(['sessions_count', 'is_unlimited']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            $table->integer('sessions_count')->nullable();
            $table->boolean('is_unlimited')->default(false);
        });
    }
};
