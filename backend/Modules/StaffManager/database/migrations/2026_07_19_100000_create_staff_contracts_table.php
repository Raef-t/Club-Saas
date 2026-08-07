<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the staff_contracts table
        if (!Schema::hasTable('staff_contracts')) {
            Schema::create('staff_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('staff_id');
                $table->string('employment_type', 50)->nullable();
                $table->decimal('base_salary', 12, 2)->default(0);
                $table->string('commission_type', 50)->nullable();
                $table->decimal('commission_rate', 5, 2)->default(0);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            });
        }

        // 2. Data Migration: Migrate existing financial data to staff_contracts
        // To be safe in production, we do this using DB facade
        $staffMembers = DB::table('staff')->get();
        
        foreach ($staffMembers as $staff) {
            // Check if coach
            $coachDetail = null;
            if ($staff->role === 'coach') {
                $coachDetail = DB::table('coach_details')->where('staff_id', $staff->id)->first();
            }

            $commissionRate = $coachDetail ? $coachDetail->default_commission_rate : ($staff->commission_rate ?? 0);
            $commissionType = $coachDetail ? $coachDetail->commission_type : null;
            
            if (empty($commissionType) && $commissionRate > 0) {
                $commissionType = 'percentage';
            }

            DB::table('staff_contracts')->insert([
                'staff_id' => $staff->id,
                'employment_type' => $staff->employment_type,
                'base_salary' => $staff->base_salary,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop columns from staff and coach_details
        Schema::table('staff', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('staff', 'employment_type')) $columnsToDrop[] = 'employment_type';
            if (Schema::hasColumn('staff', 'base_salary')) $columnsToDrop[] = 'base_salary';
            if (Schema::hasColumn('staff', 'commission_rate')) $columnsToDrop[] = 'commission_rate';
            
            if (count($columnsToDrop) > 0) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('coach_details', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('coach_details', 'payment_type')) $columnsToDrop[] = 'payment_type';
            if (Schema::hasColumn('coach_details', 'commission_type')) $columnsToDrop[] = 'commission_type';
            if (Schema::hasColumn('coach_details', 'default_commission_rate')) $columnsToDrop[] = 'default_commission_rate';
            
            if (count($columnsToDrop) > 0) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        // Add columns back to staff
        Schema::table('staff', function (Blueprint $table) {
            $table->enum('employment_type', ['fixed_salary', 'commission_based', 'hybrid'])->default('fixed_salary');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
        });

        // Add columns back to coach_details
        Schema::table('coach_details', function (Blueprint $table) {
            $table->string('payment_type', 50)->nullable();
            $table->string('commission_type', 50)->nullable();
            $table->decimal('default_commission_rate', 5, 2)->nullable();
        });

        // We can't perfectly migrate back the exact old state (versioning would be lost),
        // but we can try to restore the active contract data.
        $activeContracts = DB::table('staff_contracts')->where('is_active', true)->get();
        foreach ($activeContracts as $contract) {
            DB::table('staff')
                ->where('id', $contract->staff_id)
                ->update([
                    'employment_type' => in_array($contract->employment_type, ['fixed_salary', 'commission_based', 'hybrid']) ? $contract->employment_type : 'fixed_salary',
                    'base_salary' => $contract->base_salary,
                    'commission_rate' => $contract->commission_rate,
                ]);

            DB::table('coach_details')
                ->where('staff_id', $contract->staff_id)
                ->update([
                    'payment_type' => $contract->employment_type,
                    'commission_type' => $contract->commission_type,
                    'default_commission_rate' => $contract->commission_rate,
                ]);
        }

        Schema::dropIfExists('staff_contracts');
    }
};
