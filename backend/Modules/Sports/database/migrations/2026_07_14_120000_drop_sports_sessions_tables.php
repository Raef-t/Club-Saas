<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First drop bookings table because it has foreign key to sessions
        Schema::dropIfExists('sports_session_bookings');
        
        // Then drop sessions table
        Schema::dropIfExists('sports_sessions');
    }

    public function down(): void
    {
        // Since we are permanently moving to session_templates, we don't need a full rollback schema here.
        // But if required, we would recreate them here.
    }
};
