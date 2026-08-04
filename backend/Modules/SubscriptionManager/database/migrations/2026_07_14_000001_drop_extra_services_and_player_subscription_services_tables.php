<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('player_subscription_services');
        Schema::dropIfExists('extra_services');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not supporting rollback for dropped tables unless required
    }
};
