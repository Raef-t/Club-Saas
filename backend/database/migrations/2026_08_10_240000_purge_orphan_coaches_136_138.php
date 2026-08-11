<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $targetPeopleIds = [136, 138];
        $targetUserIds = [101, 103];

        // 1. Delete photo files from storage disk if present
        if (Schema::hasTable('people')) {
            $photos = DB::table('people')
                ->whereIn('id', $targetPeopleIds)
                ->pluck('photo_url')
                ->filter();

            foreach ($photos as $photo) {
                $relativePath = ltrim(str_replace(['storage/', 'public/'], '', $photo), '/');
                $fullPath = storage_path('app/public/' . $relativePath);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // 2. Delete related records for people 136 and 138
            if (Schema::hasTable('person_contacts')) {
                DB::table('person_contacts')->whereIn('person_id', $targetPeopleIds)->delete();
            }
            if (Schema::hasTable('person_qr_codes')) {
                DB::table('person_qr_codes')->whereIn('person_id', $targetPeopleIds)->delete();
            }
            if (Schema::hasTable('wallets')) {
                DB::table('wallets')->whereIn('person_id', $targetPeopleIds)->delete();
            }

            // Hard delete from people table
            DB::table('people')->whereIn('id', $targetPeopleIds)->delete();
        }

        // 3. Delete related tokens, devices, and users for 101 and 103
        if (Schema::hasTable('authentication_users')) {
            if (Schema::hasTable('user_devices')) {
                DB::table('user_devices')->whereIn('user_id', $targetUserIds)->delete();
            }
            if (Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'Modules\Authentication\Models\User')
                    ->whereIn('tokenable_id', $targetUserIds)
                    ->delete();
            }

            // Hard delete from authentication_users table
            DB::table('authentication_users')->whereIn('id', $targetUserIds)->delete();
            DB::table('authentication_users')->whereIn('person_id', $targetPeopleIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Permanent purge, no rollback needed
    }
};
