<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('command_executions', function (Blueprint $table) {
            $table->id();
            $table->string('command_signature')->index();
            $table->string('period')->index(); // '2026-07' or '2026-07-15'
            $table->timestamp('executed_at');
            $table->string('status')->default('success');
            $table->timestamps();

            // Ensure a command runs only once per period
            $table->unique(['command_signature', 'period']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('command_executions');
    }
};
