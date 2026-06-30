<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formula_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained('formulas')->cascadeOnDelete();
            $table->string('variable_name');                        // weight, height, bmr_result
            $table->enum('source_type', ['input', 'measurement', 'computed']);
            // عند source_type = 'measurement': اسم العمود في member_measurements
            $table->string('db_column')->nullable();
            // عند source_type = 'computed': مفتاح المعادلة المصدر
            $table->string('computed_formula_key')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->unique(['formula_id', 'variable_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formula_variables');
    }
};
