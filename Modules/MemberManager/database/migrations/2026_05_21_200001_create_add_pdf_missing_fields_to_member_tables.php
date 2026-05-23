<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('how_heard_about_us')->nullable()->after('join_date');
        });

        Schema::table('member_measurements', function (Blueprint $table) {
            $table->decimal('chest_circumference', 5, 2)->nullable()->after('waist_circumference');
            $table->decimal('thigh_circumference', 5, 2)->nullable()->after('chest_circumference');
            $table->decimal('arm_circumference', 5, 2)->nullable()->after('thigh_circumference');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('how_heard_about_us');
        });

        Schema::table('member_measurements', function (Blueprint $table) {
            $table->dropColumn([
                'chest_circumference',
                'thigh_circumference',
                'arm_circumference',
            ]);
        });
    }
};
