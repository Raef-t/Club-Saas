<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('player_subscriptions', 'is_discount')) {
                $table->boolean('is_discount')->default(false)->after('remaining_amount');
            }
            if (!Schema::hasColumn('player_subscriptions', 'discount_percentage')) {
                $table->decimal('discount_percentage', 8, 2)->default(0)->after('is_discount');
            }
            if (!Schema::hasColumn('player_subscriptions', 'coach_discount_percentage')) {
                $table->decimal('coach_discount_percentage', 8, 2)->default(0)->after('discount_percentage');
            }
            if (!Schema::hasColumn('player_subscriptions', 'branch_discount_percentage')) {
                $table->decimal('branch_discount_percentage', 8, 2)->default(0)->after('coach_discount_percentage');
            }
            if (!Schema::hasColumn('player_subscriptions', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('branch_discount_percentage');
            }
            if (!Schema::hasColumn('player_subscriptions', 'discount_reason')) {
                $table->string('discount_reason')->nullable()->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $columns = ['is_discount', 'discount_percentage', 'coach_discount_percentage', 'branch_discount_percentage', 'discount_amount', 'discount_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('player_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
