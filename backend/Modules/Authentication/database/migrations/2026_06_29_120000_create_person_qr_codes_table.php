<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->string('code', 100)->unique();

            // 0 = Sunday, 1 = Monday, ... 6 = Saturday
            // Each person gets exactly one code per day of the week
            $table->tinyInteger('day_of_week')->unsigned()->comment('0=Sun,1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat');

            $table->timestamps();

            $table->foreign('person_id')
                  ->references('id')
                  ->on('people')
                  ->onDelete('cascade');

            // Enforce one code per person per day
            $table->unique(['person_id', 'day_of_week']);
            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_qr_codes');
    }
};
