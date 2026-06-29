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
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // Assuming Translatable since Activity name is translatable
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('activity_type_id')->nullable()->after('type')->constrained('activity_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['activity_type_id']);
            $table->dropColumn('activity_type_id');
        });

        Schema::dropIfExists('activity_types');
    }
};
