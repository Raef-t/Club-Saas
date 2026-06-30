<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('specialization');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('contract_type', 50)->nullable()->after('end_date');
            $table->string('work_type', 50)->nullable()->after('contract_type');
            $table->string('work_status', 50)->default('active')->after('work_type');
            $table->string('salary_type', 50)->nullable()->after('work_status');
            $table->string('employee_type', 50)->nullable()->after('salary_type');
            $table->text('other_tasks')->nullable()->after('employee_type');
            $table->string('gym_type', 20)->nullable()->after('other_tasks');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn([
                'start_date', 'end_date', 'contract_type', 'work_type',
                'work_status', 'salary_type', 'employee_type', 'other_tasks', 'gym_type',
            ]);
        });
    }
};
