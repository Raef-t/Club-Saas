<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_contracts', 'private_commission_rate')) {
                $table->decimal('private_commission_rate', 5, 2)
                      ->nullable()
                      ->default(0)
                      ->after('commission_rate')
                      ->comment('نسبة الكوتش من اشتراكات أجهزة خاص (%)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('staff_contracts', 'private_commission_rate')) {
                $table->dropColumn('private_commission_rate');
            }
        });
    }
};
