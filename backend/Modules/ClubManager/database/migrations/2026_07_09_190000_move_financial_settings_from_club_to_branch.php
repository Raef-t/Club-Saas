<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_club_commission_percentage',
                'default_coach_commission_percentage',
                'default_employee_salary'
            ]);
        });

        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->decimal('default_club_commission_percentage', 5, 2)->nullable();
            $table->decimal('default_coach_commission_percentage', 5, 2)->nullable();
            $table->decimal('default_employee_salary', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_settings');

        Schema::table('club_settings', function (Blueprint $table) {
            $table->decimal('default_club_commission_percentage', 5, 2)->nullable();
            $table->decimal('default_coach_commission_percentage', 5, 2)->nullable();
            $table->decimal('default_employee_salary', 10, 2)->nullable();
        });
    }
};
