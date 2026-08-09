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
        Schema::table('authentication_users', function (Blueprint $table) {
            if (!Schema::hasColumn('authentication_users', 'custom_username')) {
                $table->string('custom_username')->nullable()->unique()->after('username');
            }
            if (!Schema::hasColumn('authentication_users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(true)->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authentication_users', function (Blueprint $table) {
            if (Schema::hasColumn('authentication_users', 'custom_username')) {
                $table->dropColumn('custom_username');
            }
            if (Schema::hasColumn('authentication_users', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};
