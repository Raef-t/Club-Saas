<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->after('plan_id')->default(0);
            $table->decimal('remaining_amount', 12, 2)->after('paid_amount')->default(0);
            $table->unsignedBigInteger('coach_id')->nullable()->after('member_id');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'remaining_amount', 'coach_id', 'notes']);
        });
    }
};
