<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_safes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // صندوق الإدارة، صندوق فرع المزة
            $table->foreignId('account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->enum('currency', ['USD', 'SYP'])->default('USD');
            $table->unsignedBigInteger('responsible_user_id')->nullable(); // المسؤول
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_safes');
    }
};
