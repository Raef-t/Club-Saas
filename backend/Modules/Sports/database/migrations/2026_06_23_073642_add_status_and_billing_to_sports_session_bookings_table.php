<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('status');
            $table->unsignedBigInteger('invoice_id')->nullable()->after('is_paid');
            // Supported statuses going forward: pending, confirmed, cancelled, completed, no_show
        });
    }

    public function down(): void
    {
        Schema::table('sports_session_bookings', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'invoice_id']);
        });
    }
};
