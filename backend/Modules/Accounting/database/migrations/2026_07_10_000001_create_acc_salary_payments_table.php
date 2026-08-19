<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('safe_id')->constrained('acc_safes')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('acc_periods')->restrictOnDelete();
            $table->decimal('amount', 15, 4);
            $table->string('currency', 10); // USD, SYP
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('acc_journals')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_salary_payments');
    }
};
