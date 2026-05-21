<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->integer('children_count')->nullable()->after('social_status');
            $table->string('how_did_you_hear', 100)->nullable()->after('chronic_diseases');
            $table->text('notes')->nullable()->after('how_did_you_hear');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['children_count', 'how_did_you_hear', 'notes']);
        });
    }
};
