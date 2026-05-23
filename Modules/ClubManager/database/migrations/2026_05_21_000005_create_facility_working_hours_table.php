<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('facility_working_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->integer('day_of_week')->comment('0=Sunday, 6=Saturday');
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('facility_working_hours');
    }
};
