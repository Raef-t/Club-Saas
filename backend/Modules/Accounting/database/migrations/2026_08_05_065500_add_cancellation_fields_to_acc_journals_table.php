<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acc_journals', function (Blueprint $table) {
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('reversed_journal_id');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
        });

        // Update status ENUM to include 'cancelled'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE acc_journals MODIFY COLUMN status ENUM('draft', 'posted', 'reversed', 'cancelled') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE acc_journals MODIFY COLUMN status ENUM('draft', 'posted', 'reversed') DEFAULT 'draft'");
        }

        Schema::table('acc_journals', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancellation_reason']);
        });
    }
};
