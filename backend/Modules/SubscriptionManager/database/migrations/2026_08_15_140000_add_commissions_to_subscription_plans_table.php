<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('club_commission_percentage', 5, 2)->nullable()->after('gender_restriction')->comment('نسبة النادي من الاشتراك (%)');
            $table->decimal('coach_commission_percentage', 5, 2)->nullable()->after('club_commission_percentage')->comment('نسبة الكوتش من الاشتراك (%)');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['club_commission_percentage', 'coach_commission_percentage']);
        });
    }
};
