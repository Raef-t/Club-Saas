<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('acc_journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('acc_accounts')->restrictOnDelete();
            // القيد المزدوج: مدين ودائن لكل عملة
            $table->decimal('debit_usd', 18, 4)->default(0);
            $table->decimal('credit_usd', 18, 4)->default(0);
            $table->decimal('debit_syp', 20, 2)->default(0);
            $table->decimal('credit_syp', 20, 2)->default(0);
            $table->string('memo', 255)->nullable(); // ملاحظة السطر
            $table->timestamps();

            $table->index('journal_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_journal_entries');
    }
};
