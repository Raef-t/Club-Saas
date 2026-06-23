<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // رمز الحساب: 1101, 4001
            $table->string('name', 150); // اسم الحساب بالعربي
            $table->string('name_en', 150)->nullable(); // الاسم الإنجليزي
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('currency', ['USD', 'SYP', 'BOTH'])->default('BOTH');
            $table->unsignedBigInteger('parent_id')->nullable(); // للحسابات الفرعية
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_entry')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('acc_accounts')->nullOnDelete();
            $table->index(['type', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_accounts');
    }
};
