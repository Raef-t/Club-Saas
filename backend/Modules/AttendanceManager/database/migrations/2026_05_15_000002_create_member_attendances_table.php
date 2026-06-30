<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('member_attendances')) {
            Schema::dropIfExists('member_attendances');
        }
        
        // Also drop player_attendances from SubscriptionManager
        if (Schema::hasTable('player_attendances')) {
            Schema::dropIfExists('player_attendances');
        }

        Schema::create('member_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id'); // Domain reference
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index for fast lookups by member_id
            $table->index('member_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_attendances');
    }
};
