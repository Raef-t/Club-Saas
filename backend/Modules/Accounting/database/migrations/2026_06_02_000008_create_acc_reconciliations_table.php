<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safe_id')->constrained('acc_safes')->restrictOnDelete();
            $table->foreignId('period_id')->constrained('acc_periods')->restrictOnDelete();
            // أرصدة الدولار
            $table->decimal('system_balance_usd', 18, 4)->default(0); // رصيد النظام
            $table->decimal('physical_balance_usd', 18, 4)->default(0); // الرصيد الفعلي
            $table->decimal('difference_usd', 18, 4)->storedAs('physical_balance_usd - system_balance_usd');
            // أرصدة الليرة
            $table->decimal('system_balance_syp', 20, 2)->default(0);
            $table->decimal('physical_balance_syp', 20, 2)->default(0);
            $table->decimal('difference_syp', 20, 2)->storedAs('physical_balance_syp - system_balance_syp');
            $table->unsignedBigInteger('reconciled_by');
            $table->timestamp('reconciled_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['safe_id', 'period_id']); // تسوية واحدة لكل صندوق/فترة
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_reconciliations');
    }
};
