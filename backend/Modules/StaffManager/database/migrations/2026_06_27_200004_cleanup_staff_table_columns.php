<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove redundant columns from staff table.
     * These have been moved to coach_details / coach_certifications.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn([
                'specialization',      // → coach_details.specialization
                'commission_rate',     // → coach_details.default_commission_rate
                'certificates_held',  // → coach_certifications table
                'salary_type',        // redundant with employment_type
                'employee_type',      // redundant with role
                'gym_type',           // → coach_details.gym_type (coach-specific)
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('specialization')->nullable()->after('commission_rate');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('base_salary');
            $table->json('certificates_held')->nullable()->after('specialization');
            $table->string('salary_type', 50)->nullable()->after('work_status');
            $table->string('employee_type', 50)->nullable()->after('salary_type');
            $table->string('gym_type', 20)->nullable()->after('other_tasks');
        });
    }
};
