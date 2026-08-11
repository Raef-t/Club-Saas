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
        Schema::create('locker_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('locker_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('status')->default('active'); // active, expired, cancelled
            
            $table->timestamps();

            // Note: skipping foreign keys to avoid coupling, or add them if sure about table names
            // $table->foreign('locker_id')->references('id')->on('lockers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locker_reservations');
    }
};
