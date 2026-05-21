<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('person_id');
            $table->string('work_type', 50)->nullable()->after('experience_years');
            $table->date('start_date')->nullable()->after('work_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('certificates')->nullable()->after('end_date');
            $table->string('payment_type', 50)->nullable()->after('certificates');
            $table->string('commission_type', 50)->nullable()->after('payment_type');
            $table->decimal('commission_rate', 5, 2)->nullable()->after('commission_type');
            $table->decimal('salary', 12, 2)->nullable()->after('commission_rate');
            $table->decimal('working_hours', 5, 2)->nullable()->after('salary');
            $table->json('unavailable_times')->nullable()->after('working_hours');
            $table->string('gym_type', 20)->nullable()->after('unavailable_times');
        });
    }

    public function down(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id', 'work_type', 'start_date', 'end_date',
                'certificates', 'payment_type', 'commission_type', 'commission_rate',
                'salary', 'working_hours', 'unavailable_times', 'gym_type',
            ]);
        });
    }
};
