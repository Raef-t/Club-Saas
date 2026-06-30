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
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cash_register_id')) {
                // Drop foreign key if it exists
                $conn = Schema::getConnection();
                $dbDriver = $conn->getDriverName();
                if ($dbDriver !== 'sqlite') {
                    try {
                        $table->dropForeign(['cash_register_id']);
                    } catch (\Exception $e) {
                        // Ignore if foreign key does not exist
                    }
                }
                
                $table->dropColumn('cash_register_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'cash_register_id')) {
                $table->unsignedBigInteger('cash_register_id')->nullable();
                // Optionally add foreign key back if needed
            }
        });
    }
};
