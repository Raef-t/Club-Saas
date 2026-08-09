<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_qr_codes', function (Blueprint $table) {
            $table->tinyInteger('day_of_week')->unsigned()->nullable()->change();
        });

        // Clean up existing staff/coach QR codes so each staff member has 1 code with day_of_week = null
        $staffPersonIds = DB::table('staff')->pluck('person_id')->filter()->unique();

        foreach ($staffPersonIds as $personId) {
            $codes = DB::table('person_qr_codes')
                ->where('person_id', $personId)
                ->orderBy('id')
                ->get();

            if ($codes->isNotEmpty()) {
                $first = $codes->first();
                DB::table('person_qr_codes')
                    ->where('id', $first->id)
                    ->update(['day_of_week' => null]);

                DB::table('person_qr_codes')
                    ->where('person_id', $personId)
                    ->where('id', '!=', $first->id)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        Schema::table('person_qr_codes', function (Blueprint $table) {
            $table->tinyInteger('day_of_week')->unsigned()->nullable(false)->change();
        });
    }
};
