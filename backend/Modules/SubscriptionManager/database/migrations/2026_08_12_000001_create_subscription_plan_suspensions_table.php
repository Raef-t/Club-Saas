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
        if (!Schema::hasTable('subscription_plan_suspensions')) {
            Schema::create('subscription_plan_suspensions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->foreignId('coach_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->date('suspend_start_date');
                $table->date('suspend_end_date');
                $table->date('actual_end_date')->nullable();
                $table->integer('suspension_days');
                $table->text('reason')->nullable();
                $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('active');
                $table->integer('affected_subscribers_count')->default(0);
                $table->timestamp('notified_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('authentication_users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_suspensions');
    }
};
