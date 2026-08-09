<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            
            // Polymorphic relation for SportSessionTemplate or Staff
            $table->string('evaluatable_type');
            $table->unsignedBigInteger('evaluatable_id');
            
            $table->tinyInteger('rating')->unsigned(); // 1 to 5
            $table->text('comment')->nullable();
            
            $table->timestamps();

            // Index for polymorphic relation
            $table->index(['evaluatable_type', 'evaluatable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_evaluations');
    }
};
