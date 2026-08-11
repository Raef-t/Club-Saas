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
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'member_name')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('member_name')->nullable()->after('member_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'member_name')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('member_name');
            });
        }
    }
};
