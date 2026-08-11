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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('attendable_type');
            $table->unsignedBigInteger('attendable_id');
            $table->unsignedBigInteger('branch_id')->index();
            
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            
            $table->string('status', 50)->default('checked_in');
            
            $table->timestamps();

            // Compound index for branch-specific attendee queries (e.g., getting staff history or player logs)
            $table->index(['branch_id', 'attendable_type', 'attendable_id'], 'branch_attendable_idx');
            // Index for date-range dashboard reports scoped by branch
            $table->index(['branch_id', 'check_in_at'], 'branch_check_in_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
