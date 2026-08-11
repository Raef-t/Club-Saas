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
        Schema::dropIfExists('staff_income_entries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Table dropped as part of shift to on-the-fly calculation.
    }
};
