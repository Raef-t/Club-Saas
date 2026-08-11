<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('months_count')->default(1)->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('player_subscriptions', function (Blueprint $table) {
            $table->dropColumn('months_count');
        });
    }
};
