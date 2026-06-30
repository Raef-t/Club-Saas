<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('capital_account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->foreignId('drawings_account_id')->nullable()->constrained('acc_accounts')->nullOnDelete();
            $table->decimal('profit_share_pct', 5, 2)->default(0); // النسبة من الأرباح %
            $table->date('joined_at');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_partners');
    }
};
