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
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('deductions', 12, 2)->default(0)->after('commission_pay');
            $table->string('deduction_reason')->nullable()->after('deductions');
            $table->decimal('bonuses', 12, 2)->default(0)->after('deduction_reason');
            $table->string('bonus_reason')->nullable()->after('bonuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['deductions', 'deduction_reason', 'bonuses', 'bonus_reason']);
        });
    }
};
