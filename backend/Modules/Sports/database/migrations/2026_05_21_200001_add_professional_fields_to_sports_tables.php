<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('type', 50)->default('group_class')->after('description')->comment('open_gym, group_class, personal_training');
            $table->integer('default_capacity')->nullable()->after('type');
        });

        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->string('gender_allowed', 20)->nullable()->after('max_players')->comment('Overrides activity gender if set');
        });
    }

    public function down(): void
    {
        Schema::table('sports_sessions', function (Blueprint $table) {
            $table->dropColumn('gender_allowed');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['type', 'default_capacity']);
        });
    }
};
