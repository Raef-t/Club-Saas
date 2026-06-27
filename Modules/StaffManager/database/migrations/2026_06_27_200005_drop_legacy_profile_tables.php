<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop legacy profile tables that have been replaced by:
     * - coach_profiles → coach_details (in StaffManager)
     * - staff_profiles → staff table already covers this role
     */
    public function up(): void
    {
        Schema::dropIfExists('coach_profiles');
        Schema::dropIfExists('staff_profiles');
    }

    public function down(): void
    {
        // Recreate tables if rollback is needed
        Schema::create('coach_profiles', function ($table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('specialization');
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->string('work_type', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('certificates')->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->string('commission_type', 50)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->decimal('working_hours', 5, 2)->nullable();
            $table->json('unavailable_times')->nullable();
            $table->string('gym_type', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('staff_profiles', function ($table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->string('job_title');
            $table->timestamps();
        });
    }
};
