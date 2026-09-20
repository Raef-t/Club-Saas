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
        if (Schema::hasTable('locker_reservations')) {
            Schema::table('locker_reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('locker_reservations', 'refund_safe_id')) {
                    $table->unsignedBigInteger('refund_safe_id')->nullable()->after('refund_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('locker_reservations')) {
            Schema::table('locker_reservations', function (Blueprint $table) {
                if (Schema::hasColumn('locker_reservations', 'refund_safe_id')) {
                    $table->dropColumn('refund_safe_id');
                }
            });
        }
    }
};
