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
                if (!Schema::hasColumn('locker_reservations', 'is_refund')) {
                    $table->boolean('is_refund')->default(false)->after('reason');
                }
                if (!Schema::hasColumn('locker_reservations', 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->nullable()->after('is_refund');
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
                if (Schema::hasColumn('locker_reservations', 'refund_amount')) {
                    $table->dropColumn('refund_amount');
                }
                if (Schema::hasColumn('locker_reservations', 'is_refund')) {
                    $table->dropColumn('is_refund');
                }
            });
        }
    }
};
