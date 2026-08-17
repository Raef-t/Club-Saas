<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the subscription_revenue_splits table.
 *
 * Purpose: Financial snapshot (immutable) for private subscriptions (أجهزة خاص).
 * Stores the exact club/coach split at the moment of purchase.
 * This data never changes retroactively, even if settings/percentages are updated later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_revenue_splits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('player_subscription_id')
                  ->constrained('player_subscriptions')
                  ->onUpdate('cascade')
                  ->onDelete('restrict')
                  ->comment('الاشتراك الفعلي للاعب');

            $table->unsignedBigInteger('coach_id')
                  ->nullable()
                  ->comment('الكوتش المرتبط بالاشتراك الخاص');

            $table->unsignedBigInteger('branch_id')
                  ->comment('الفرع لأغراض التقارير');

            // --- اللقطة المالية المجمدة (Financial Snapshot) ---
            // تُحفظ هذه القيم مرة واحدة فقط وقت الشراء ولا تتغير أبداً

            $table->decimal('total_amount', 12, 2)
                  ->comment('السعر الكلي للاشتراك وقت البيع');

            $table->decimal('club_percentage', 5, 2)
                  ->comment('نسبة الفرع من الكوتش وقت البيع (%)');

            $table->decimal('coach_percentage', 5, 2)
                  ->comment('نسبة الكوتش التي يحتفظ بها وقت البيع (%)');

            $table->decimal('club_amount', 12, 2)
                  ->comment('المبلغ الصافي للفرع (total_amount * club_percentage / 100)');

            $table->decimal('coach_amount', 12, 2)
                  ->comment('المبلغ الصافي للكوتش (total_amount * coach_percentage / 100)');

            $table->timestamps();

            // Foreign keys بدون cascade لحماية السجلات المالية التاريخية
            $table->foreign('coach_id')
                  ->references('id')->on('staff')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_revenue_splits');
    }
};
