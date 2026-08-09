<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // To avoid foreign key constraint failures if there are existing records without activity
        DB::table('staff_shifts')->delete();
        DB::table('branch_shifts')->delete();

        Schema::table('branch_shifts', function (Blueprint $table) {
            $table->foreignId('activity_id')->after('branch_id')->constrained('activities')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('branch_shifts', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropColumn('activity_id');
        });
    }
};
