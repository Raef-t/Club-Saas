<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'reason')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('status');
            });
        }

        if (Schema::hasTable('locker_reservations') && !Schema::hasColumn('locker_reservations', 'reason')) {
            Schema::table('locker_reservations', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'reason')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }

        if (Schema::hasTable('locker_reservations') && Schema::hasColumn('locker_reservations', 'reason')) {
            Schema::table('locker_reservations', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
