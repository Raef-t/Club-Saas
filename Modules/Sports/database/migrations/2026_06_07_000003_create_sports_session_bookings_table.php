<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sports_session_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sports_session_id');
            $table->unsignedBigInteger('member_id');
            $table->string('status', 50)->default('booked'); // booked, attended, cancelled
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sports_session_id')->references('id')->on('sports_sessions')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            
            $table->unique(['sports_session_id', 'member_id'], 'session_member_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sports_session_bookings');
    }
};
