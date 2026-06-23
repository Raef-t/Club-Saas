<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_journals', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 50)->unique(); // RV-2025-0001
            $table->enum('type', ['JV', 'RV', 'PV', 'SI', 'PI'])->default('JV');
            $table->foreignId('period_id')->constrained('acc_periods')->restrictOnDelete();
            $table->date('date');
            $table->text('description');
            $table->foreignId('counterparty_id')->nullable()->constrained('acc_counterparties')->nullOnDelete();
            $table->foreignId('safe_id')->nullable()->constrained('acc_safes')->nullOnDelete();
            $table->decimal('exchange_rate', 12, 4)->nullable(); // سعر الصرف لحظة القيد
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_journal_id')->nullable(); // السند العاكس
            // للربط الحيادي مع أي نظام مصدر (Payments, Payroll, etc.)
            $table->string('source_type', 100)->nullable(); // مثال: 'Payments'
            $table->unsignedBigInteger('source_id')->nullable(); // مثال: payment_id
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_journals');
    }
};
