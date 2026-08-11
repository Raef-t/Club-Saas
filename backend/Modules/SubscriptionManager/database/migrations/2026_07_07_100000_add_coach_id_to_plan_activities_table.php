<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->unsignedBigInteger('coach_id')->nullable()->after('activity_id');
                // The coach is from the Staff table (which uses the id from `staff`)
                // It is nullable at DB level to prevent errors with existing data,
                // but will be enforced via Application Logic / API Validations.
                $table->foreign('coach_id')->references('id')->on('staff')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan_activities', function (Blueprint $table) {
            if (Schema::hasColumn('plan_activities', 'coach_id')) {
                $table->dropForeign(['coach_id']);
                $table->dropColumn('coach_id');
            }
        });
    }
};
