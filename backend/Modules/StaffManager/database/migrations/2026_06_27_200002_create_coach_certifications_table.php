<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_certifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coach_detail_id');

            $table->string('name');                          // Certificate name
            $table->string('issuer')->nullable();            // Issuing organization
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('document_url')->nullable();      // Uploaded file path

            $table->timestamps();

            $table->foreign('coach_detail_id')->references('id')->on('coach_details')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_certifications');
    }
};
