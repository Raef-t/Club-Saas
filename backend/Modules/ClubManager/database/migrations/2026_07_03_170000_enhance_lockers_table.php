<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Enhance the lockers table so it becomes the single source of truth
 * for locker state. Instead of relying on attendance records to know
 * who holds a key, the locker row itself now tracks the current holder.
 *
 * Holder model (polymorphic-style, no FK constraint so guest names work too):
 *   holder_type  → 'member' | 'staff' | 'guest'
 *   holder_id    → ID in the corresponding table (null for guests)
 *   holder_name  → cached display name (or raw guest name)
 *   assigned_at  → when the key was handed out
 *
 * Status values:
 *   available    → key is at the reception desk
 *   with_member  → a registered member is holding the key
 *   with_staff   → a staff member / coach is holding the key
 *   with_guest   → an unregistered guest is holding the key
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lockers', function (Blueprint $table) {

            // ── Drop the old interim column added 2026-07-02 ─────────────────
            // holder_id + holder_type replace current_attendance_id entirely.
            if (Schema::hasColumn('lockers', 'current_attendance_id')) {
                $table->dropColumn('current_attendance_id');
            }

            // ── Polymorphic holder fields ─────────────────────────────────────
            // No foreign-key constraint intentionally: guests have no DB record.
            $table->unsignedBigInteger('holder_id')
                ->nullable()
                ->after('status')
                ->comment('ID of the person holding the key (member/staff). NULL for guests.');

            $table->string('holder_type', 50)
                ->nullable()
                ->after('holder_id')
                ->comment('member | staff | guest');

            $table->string('holder_name')
                ->nullable()
                ->after('holder_type')
                ->comment('Cached display name or raw guest name.');

            $table->timestamp('assigned_at')
                ->nullable()
                ->after('holder_name')
                ->comment('When the key was handed out.');
        });

        // ── Update status default & existing rows ─────────────────────────
        // Old statuses: 'available', 'rented'  →  New: 'available', 'with_member', 'with_staff', 'with_guest'
        // Any row that was 'rented' but has no holder context yet → keep 'available' to avoid orphans.
        DB::table('lockers')->where('status', 'rented')->update(['status' => 'available']);

        // Update column default
        Schema::table('lockers', function (Blueprint $table) {
            $table->string('status', 50)->default('available')->change();
        });
    }

    public function down(): void
    {
        Schema::table('lockers', function (Blueprint $table) {
            $table->dropColumn(['holder_id', 'holder_type', 'holder_name', 'assigned_at']);

            // Restore the interim column
            $table->unsignedBigInteger('current_attendance_id')
                ->nullable()
                ->after('status');
        });

        // Restore old status values
        DB::table('lockers')
            ->whereIn('status', ['with_member', 'with_staff', 'with_guest'])
            ->update(['status' => 'rented']);
    }
};
