<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (!Schema::hasColumn('payslips', 'status')) {
                $table->string('status', 30)->default('pending')->after('net_pay');
            }
            if (!Schema::hasColumn('payslips', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }
        });

        Schema::table('acc_salary_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('acc_salary_payments', 'payment_type')) {
                $table->string('payment_type', 30)->default('salary')->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (Schema::hasColumn('payslips', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('payslips', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('acc_salary_payments', function (Blueprint $table) {
            if (Schema::hasColumn('acc_salary_payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};
