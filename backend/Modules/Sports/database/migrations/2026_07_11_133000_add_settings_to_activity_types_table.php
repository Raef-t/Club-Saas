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
        Schema::table('activity_types', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_types', 'is_session_based')) {
                $table->boolean('is_session_based')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('activity_types', 'has_unlimited_subscribers')) {
                $table->boolean('has_unlimited_subscribers')->default(false)->after('is_session_based');
            }
            if (!Schema::hasColumn('activity_types', 'has_shifts')) {
                $table->boolean('has_shifts')->default(false)->after('has_unlimited_subscribers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropColumn([
                'is_session_based',
                'has_unlimited_subscribers',
                'has_shifts',
            ]);
        });
    }
};
