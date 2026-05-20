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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->json('name'); // Translatable: {"ar": "...", "en": "..."}
            $table->text('description')->nullable();
            $table->boolean('is_private_equipment')->default(false);
            $table->string('gender_allowed')->default('mixed'); // male, female, mixed
            $table->boolean('is_active')->default(true);
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
