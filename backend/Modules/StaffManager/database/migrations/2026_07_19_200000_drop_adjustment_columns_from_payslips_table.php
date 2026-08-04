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
            $table->dropColumn([
                'deductions',
                'deduction_reason',
                'bonuses',
                'bonus_reason'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('deductions', 10, 2)->default(0)->after('commission_pay');
            $table->text('deduction_reason')->nullable()->after('deductions');
            $table->decimal('bonuses', 10, 2)->default(0)->after('deduction_reason');
            $table->text('bonus_reason')->nullable()->after('bonuses');
        });
    }
};
