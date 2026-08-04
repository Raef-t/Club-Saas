<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_session_template_id');
            $table->unsignedBigInteger('coach_id')->nullable()->comment('The coach who canceled or was assigned');
            $table->date('date')->comment('The date this exception applies to');
            $table->string('status', 50)->default('canceled');
            $table->text('reason')->nullable();
            
            $table->timestamps();

            $table->foreign('sport_session_template_id', 'se_sst_id_fk')
                  ->references('id')
                  ->on('sport_session_templates')
                  ->onDelete('cascade');
                  
            $table->foreign('coach_id')
                  ->references('id')
                  ->on('staff')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_exceptions');
    }
};
