<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->decimal('private_subscription_commission', 5, 2)
                  ->default(0.00)
                  ->after('default_coach_commission_percentage')
                  ->comment('نسبة عمولة النادي من الاشتراكات الخاصة (%)، الافتراضي 0%');
        });
    }

    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn('private_subscription_commission');
        });
    }
};
