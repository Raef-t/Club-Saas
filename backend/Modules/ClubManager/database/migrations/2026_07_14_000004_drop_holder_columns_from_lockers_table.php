<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropColumn(['holder_id', 'holder_type', 'holder_name', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->unsignedBigInteger('holder_id')->nullable();
            $table->string('holder_type')->nullable();
            $table->string('holder_name')->nullable();
            $table->timestamp('assigned_at')->nullable();
        });
    }
};
