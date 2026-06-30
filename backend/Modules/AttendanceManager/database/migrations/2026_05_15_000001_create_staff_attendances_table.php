<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Check if old table exists, if so drop it or rename it.
        // For clean transition, we will drop the old scattered ones if they exist
        // to avoid conflicts, or just assume this is a fresh migration.
        if (Schema::hasTable('staff_attendances')) {
            Schema::dropIfExists('staff_attendances');
        }

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id'); // Domain reference, no foreign key constraint across modules
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index for fast lookups by staff_id
            $table->index('staff_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_attendances');
    }
};
