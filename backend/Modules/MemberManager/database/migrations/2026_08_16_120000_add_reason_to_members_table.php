<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('members') && !Schema::hasColumn('members', 'reason')) {
            Schema::table('members', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('membership_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('members') && Schema::hasColumn('members', 'reason')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
