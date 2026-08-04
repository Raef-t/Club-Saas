<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->decimal('default_club_commission_percentage', 5, 2)->nullable()->after('bg_image_url');
            $table->decimal('default_coach_commission_percentage', 5, 2)->nullable()->after('default_club_commission_percentage');
            $table->decimal('default_employee_salary', 10, 2)->nullable()->after('default_coach_commission_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('club_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_club_commission_percentage',
                'default_coach_commission_percentage',
                'default_employee_salary',
            ]);
        });
    }
};
