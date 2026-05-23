<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('club_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->json('theme_colors')->nullable();
            $table->string('language', 10)->default('all')->comment('ar, en, all');
            $table->json('enabled_features')->nullable();
            $table->string('bg_image_url')->nullable();
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('club_settings');
    }
};
