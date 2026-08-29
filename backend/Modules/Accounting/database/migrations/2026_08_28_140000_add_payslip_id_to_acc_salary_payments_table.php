<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acc_salary_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('acc_salary_payments', 'payslip_id')) {
                $table->foreignId('payslip_id')->nullable()->after('period_id')->constrained('payslips')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('acc_salary_payments', function (Blueprint $table) {
            if (Schema::hasColumn('acc_salary_payments', 'payslip_id')) {
                $table->dropForeign(['payslip_id']);
                $table->dropColumn('payslip_id');
            }
        });
    }
};
