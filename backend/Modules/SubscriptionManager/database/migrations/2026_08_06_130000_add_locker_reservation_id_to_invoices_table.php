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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'locker_reservation_id')) {
                $table->unsignedBigInteger('locker_reservation_id')->nullable()->after('player_subscription_id');
                $table->foreign('locker_reservation_id')->references('id')->on('locker_reservations')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'locker_reservation_id')) {
                $table->dropForeign(['locker_reservation_id']);
                $table->dropColumn('locker_reservation_id');
            }
        });
    }
};
