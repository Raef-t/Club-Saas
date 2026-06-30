<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to staff
        Schema::table('staff', function (Blueprint $table) {
            $table->string('shift_type', 50)->nullable()->after('contract_type');
            $table->json('certificates_held')->nullable()->after('specialization');
        });

        // 2. Staff Branches (Multi-branch support without hard foreign key to branches table to avoid coupling)
        Schema::create('staff_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('branch_id')->comment('References branches.id in ClubManager');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            // Deliberately omitting foreign key to `branches` to respect zero cross-coupling constraint.
        });

        // 3. Staff Unavailabilities
        Schema::create('staff_unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_unavailabilities');
        Schema::dropIfExists('staff_branches');
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['shift_type', 'certificates_held']);
        });
    }
};
