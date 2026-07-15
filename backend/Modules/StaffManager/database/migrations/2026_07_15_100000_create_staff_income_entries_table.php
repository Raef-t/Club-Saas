<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff_income_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            
            // Type of entry: 'base_salary', 'commission', 'bonus', 'deduction', etc.
            $table->string('type')->default('commission');
            
            // Polymorphic relation to trace where the income came from (e.g. PlanSubscription)
            $table->nullableMorphs('source');
            
            // For commissions, the original amount the percentage was applied to
            $table->decimal('base_amount', 10, 2)->nullable();
            
            // The percentage applied (e.g. 20.00 for 20%)
            $table->decimal('percentage_applied', 5, 2)->nullable();
            
            // The final calculated or fixed amount for this entry
            $table->decimal('amount', 10, 2);
            
            // Status of the entry (e.g., pending payment, paid)
            $table->string('status')->default('pending');
            
            $table->text('description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_income_entries');
    }
};
