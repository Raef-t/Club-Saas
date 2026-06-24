<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop legacy attendance tables that have been replaced by the unified
 * `attendances` table with Polymorphic relationships.
 *
 * Replaced by:
 *   attendances.attendable_type = 'member' → formerly member_attendances
 *   attendances.attendable_type = 'staff'  → formerly staff_attendances
 *   attendances.attendable_type = 'member' → formerly player_attendances
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy tables — all data now lives in `attendances`
        Schema::dropIfExists('member_attendances');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('player_attendances');
    }

    public function down(): void
    {
        // Re-create stubs if rollback is needed (data is NOT restored)
        Schema::create('member_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->timestamps();
        });
    }
};
