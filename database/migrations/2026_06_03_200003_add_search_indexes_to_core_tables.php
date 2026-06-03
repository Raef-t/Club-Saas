<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people')) {
            Schema::table('people', function (Blueprint $table) {
                $table->index('full_name');
                $table->index('mobile_1');
                $table->index('email');
                $table->index('national_id');
            });
        }

        if (Schema::hasTable('player_subscriptions')) {
            Schema::table('player_subscriptions', function (Blueprint $table) {
                $table->index('status');
                $table->index('start_date');
                $table->index('end_date');
            });
        }

        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->index('role');
                $table->index('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropIndex(['role']);
                $table->dropIndex(['is_active']);
            });
        }

        if (Schema::hasTable('player_subscriptions')) {
            Schema::table('player_subscriptions', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['start_date']);
                $table->dropIndex(['end_date']);
            });
        }

        if (Schema::hasTable('people')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropIndex(['full_name']);
                $table->dropIndex(['mobile_1']);
                $table->dropIndex(['email']);
                $table->dropIndex(['national_id']);
            });
        }
    }
};
