<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // حذف barcode_qr_code من جدول members
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'barcode_qr_code')) {
                $table->dropUnique(['barcode_qr_code']);
                $table->dropIndex(['barcode_qr_code']);
                $table->dropColumn('barcode_qr_code');
            }
        });

        // حذف qr_code من جدول staff
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'qr_code')) {
                $table->dropUnique(['qr_code']);
                $table->dropColumn('qr_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('barcode_qr_code')->nullable()->unique()->index();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->unique();
        });
    }
};
