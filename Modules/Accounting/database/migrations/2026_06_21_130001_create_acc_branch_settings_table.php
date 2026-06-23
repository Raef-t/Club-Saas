<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained('branches')->cascadeOnDelete();
            $table->foreignId('default_safe_id')->nullable()->constrained('acc_safes')->nullOnDelete();
            $table->string('cash_usd_account_code', 50)->nullable();
            $table->string('cash_syp_account_code', 50)->nullable();
            $table->string('revenue_account_code', 50)->nullable();
            $table->string('expense_account_code', 50)->nullable();
            $table->json('supported_currencies')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_branch_settings');
    }
};
