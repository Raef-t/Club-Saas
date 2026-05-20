<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();
            $table->json('subject')->nullable(); 
            $table->json('content'); 
            $table->enum('channel', ['sms', 'email', 'whatsapp', 'push'])->default('sms');
            $table->boolean('is_active')->default(true);
            $table->timestamps();


        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
