<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->boolean('is_visible')->default(true)->after('guard_name');
        });

        // Initialize Arabic names and visibility for existing core roles
        $rolesMap = [
            'super_admin' => ['name_ar' => 'مدير النظام العام', 'is_visible' => false],
            'admin'       => ['name_ar' => 'مدير النادي',       'is_visible' => true],
            'coach'       => ['name_ar' => 'مدرب',              'is_visible' => true],
            'player'      => ['name_ar' => 'مشترك / لاعب',     'is_visible' => true],
            'accountant'  => ['name_ar' => 'محاسب',             'is_visible' => true],
            'reception'   => ['name_ar' => 'موظف استقبال',      'is_visible' => true],
            'cleaner'     => ['name_ar' => 'عامل نظافة',        'is_visible' => true],
        ];

        foreach ($rolesMap as $roleName => $data) {
            DB::table('roles')->where('name', $roleName)->update($data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'is_visible']);
        });
    }
};
