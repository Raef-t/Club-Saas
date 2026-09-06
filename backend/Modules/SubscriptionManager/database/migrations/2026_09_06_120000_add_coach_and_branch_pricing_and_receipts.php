<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'coach_price')) {
                $table->decimal('coach_price', 12, 2)->nullable()->after('base_price')->comment('سعر الكوتش للاشتراك الخاص');
            }
            if (!Schema::hasColumn('subscription_plans', 'branch_price')) {
                $table->decimal('branch_price', 12, 2)->nullable()->after('coach_price')->comment('سعر الفرع / النادي للاشتراك الخاص');
            }
        });

        Schema::table('player_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('player_subscriptions', 'coach_receipt_number')) {
                $table->string('coach_receipt_number')->nullable()->after('notes')->comment('رقم إيصال دفعة الكوتش');
            }
            if (!Schema::hasColumn('player_subscriptions', 'branch_receipt_number')) {
                $table->string('branch_receipt_number')->nullable()->after('coach_receipt_number')->comment('رقم إيصال دفعة النادي');
            }
        });

        Schema::table('subscription_revenue_splits', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_revenue_splits', 'coach_receipt_number')) {
                $table->string('coach_receipt_number')->nullable()->after('coach_amount')->comment('رقم إيصال دفعة الكوتش');
            }
            if (!Schema::hasColumn('subscription_revenue_splits', 'branch_receipt_number')) {
                $table->string('branch_receipt_number')->nullable()->after('coach_receipt_number')->comment('رقم إيصال دفعة النادي');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('subscription_plans', 'coach_price')) {
                $cols[] = 'coach_price';
            }
            if (Schema::hasColumn('subscription_plans', 'branch_price')) {
                $cols[] = 'branch_price';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('player_subscriptions', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('player_subscriptions', 'coach_receipt_number')) {
                $cols[] = 'coach_receipt_number';
            }
            if (Schema::hasColumn('player_subscriptions', 'branch_receipt_number')) {
                $cols[] = 'branch_receipt_number';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('subscription_revenue_splits', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('subscription_revenue_splits', 'coach_receipt_number')) {
                $cols[] = 'coach_receipt_number';
            }
            if (Schema::hasColumn('subscription_revenue_splits', 'branch_receipt_number')) {
                $cols[] = 'branch_receipt_number';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
