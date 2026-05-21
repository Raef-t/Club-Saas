<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->json('name');
            $table->string('gender_restriction', 20)->default('mixed')->index();
            $table->string('type', 50)->nullable()->comment('e.g. gym, pool, classroom');
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
        });
    }
    public function down(): void { Schema::dropIfExists('branches'); }
};