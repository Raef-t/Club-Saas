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
        if (Schema::hasTable('payslips') && !Schema::hasColumn('payslips', 'staff_name')) {
            Schema::table('payslips', function (Blueprint $table) {
                $table->string('staff_name')->nullable()->after('staff_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payslips') && Schema::hasColumn('payslips', 'staff_name')) {
            Schema::table('payslips', function (Blueprint $table) {
                $table->dropColumn('staff_name');
            });
        }
    }
};
