<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['members', 'player_subscriptions', 'invoices', 'payments', 'staff'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('id');
                    // We don't strictly enforce a foreign key here to avoid cross-module tight coupling issues 
                    // during rollbacks, but logically it references authentication_users.id
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['members', 'player_subscriptions', 'invoices', 'payments', 'staff'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'created_by')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('created_by');
                });
            }
        }
    }
};
