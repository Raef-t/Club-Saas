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
            $table->unsignedBigInteger('club_id')->index();
            $table->string('attendable_type');
            $table->unsignedBigInteger('attendable_id');
            $table->unsignedBigInteger('branch_id')->index();
            
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            
            $table->string('status', 50)->default('checked_in');
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            // Compound index for club-specific attendee queries (e.g., getting staff history or player logs)
            $table->index(['club_id', 'attendable_type', 'attendable_id'], 'club_attendable_idx');
            // Index for date-range dashboard reports scoped by club
            $table->index(['club_id', 'check_in_at'], 'club_check_in_idx');
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
