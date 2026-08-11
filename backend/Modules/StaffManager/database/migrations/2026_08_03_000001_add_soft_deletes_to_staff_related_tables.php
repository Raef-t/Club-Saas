<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // coach_details
        Schema::table('coach_details', function (Blueprint $table) {
            $table->softDeletes();
        });

        // coach_certifications
        Schema::table('coach_certifications', function (Blueprint $table) {
            $table->softDeletes();
        });

        // staff_contracts
        Schema::table('staff_contracts', function (Blueprint $table) {
            $table->softDeletes();
        });

        // staff_shifts
        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->softDeletes();
        });

        // staff_leaves
        Schema::table('staff_leaves', function (Blueprint $table) {
            $table->softDeletes();
        });

        // staff_unavailabilities
        Schema::table('staff_unavailabilities', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('coach_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('coach_certifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('staff_contracts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('staff_shifts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('staff_leaves', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('staff_unavailabilities', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
