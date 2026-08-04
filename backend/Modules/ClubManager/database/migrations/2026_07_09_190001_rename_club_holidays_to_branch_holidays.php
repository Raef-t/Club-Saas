<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_holidays', function (Blueprint $table) {
            // First drop foreign key
            $table->dropForeign(['club_id']);
            // Drop column and recreate it as branch_id is safer in SQLite sometimes, but MySQL supports rename.
            // Let's use renameColumn.
            $table->renameColumn('club_id', 'branch_id');
        });
        
        Schema::rename('club_holidays', 'branch_holidays');
        
        Schema::table('branch_holidays', function (Blueprint $table) {
            // Re-add foreign key constraint
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('branch_holidays', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->renameColumn('branch_id', 'club_id');
        });
        
        Schema::rename('branch_holidays', 'club_holidays');
        
        Schema::table('club_holidays', function (Blueprint $table) {
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
        });
    }
};
