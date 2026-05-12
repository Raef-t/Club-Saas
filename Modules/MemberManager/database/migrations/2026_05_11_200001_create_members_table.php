<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('person_id');
            $table->string('member_number')->index();
            $table->string('barcode_qr_code')->nullable()->index();
            $table->string('membership_status')->default('active')->index(); // active, inactive, frozen, expired
            $table->date('join_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            
            // Unique member number and barcode per tenant
            $table->unique(['tenant_id', 'member_number']);
            $table->unique(['tenant_id', 'barcode_qr_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
