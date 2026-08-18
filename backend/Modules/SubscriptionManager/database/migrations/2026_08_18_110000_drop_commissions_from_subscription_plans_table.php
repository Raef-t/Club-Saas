<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('subscription_plans', 'club_commission_percentage')) {
                $columnsToDrop[] = 'club_commission_percentage';
            }
            if (Schema::hasColumn('subscription_plans', 'coach_commission_percentage')) {
                $columnsToDrop[] = 'coach_commission_percentage';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'club_commission_percentage')) {
                $table->decimal('club_commission_percentage', 5, 2)->nullable()->after('gender_restriction')->comment('نسبة النادي من الاشتراك (%)');
            }
            if (!Schema::hasColumn('subscription_plans', 'coach_commission_percentage')) {
                $table->decimal('coach_commission_percentage', 5, 2)->nullable()->after('club_commission_percentage')->comment('نسبة الكوتش من الاشتراك (%)');
            }
        });
    }
};
