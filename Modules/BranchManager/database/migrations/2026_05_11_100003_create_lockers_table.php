<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lockers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('facility_id');
            $table->string('locker_number');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Constraints

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            
            // Uniqueness constraint: Locker number must be unique within a single branch
            $table->unique(['branch_id', 'locker_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lockers');
    }
};
