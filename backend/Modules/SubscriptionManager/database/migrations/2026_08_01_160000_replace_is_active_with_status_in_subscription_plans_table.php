<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('subscription_plans', 'is_active') && !Schema::hasColumn('subscription_plans', 'status')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->string('status')->default('active')->after('base_price');
            });

            DB::table('subscription_plans')->where('is_active', true)->update(['status' => 'active']);
            DB::table('subscription_plans')->where('is_active', false)->update(['status' => 'inactive']);

            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('subscription_plans', 'status') && !Schema::hasColumn('subscription_plans', 'is_active')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('base_price');
            });

            DB::table('subscription_plans')->where('status', 'active')->update(['is_active' => true]);
            DB::table('subscription_plans')->where('status', '!=', 'active')->update(['is_active' => false]);

            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
