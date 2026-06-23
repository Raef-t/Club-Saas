<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_counterparties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150); // شركة التوريدات، أحمد خالد
            $table->enum('type', ['customer', 'vendor', 'employee', 'other'])->default('customer');
            $table->foreignId('ar_account_id')->nullable()->constrained('acc_accounts')->nullOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            // للربط الحيادي مع أي نظام خارجي (Polymorphic Reference)
            $table->string('reference_type', 100)->nullable(); // مثال: 'Student'
            $table->unsignedBigInteger('reference_id')->nullable(); // مثال: student_id
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_counterparties');
    }
};
