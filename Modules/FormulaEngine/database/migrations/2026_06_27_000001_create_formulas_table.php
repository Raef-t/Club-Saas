<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                                   // BMI
            $table->string('key')->unique();                                          // bmi
            $table->text('expression');                                               // weight / pow(height / 100, 2)
            $table->text('description')->nullable();
            $table->enum('category', ['measurement', 'general'])->default('measurement');
            $table->enum('return_type', ['float', 'int', 'bool', 'string'])->default('float');
            $table->string('unit')->nullable();                                       // kg/m², %, kcal
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);                             // محمية من الحذف
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulas');
    }
};
