<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            $coachPayments = DB::table('payments')
                ->where('reason', 'دفعة اشتراك المدرب')
                ->get();

            foreach ($coachPayments as $payment) {
                // 1. Cancel associated journal in accounting if it exists and is posted
                if (Schema::hasTable('acc_journals')) {
                    $journal = DB::table('acc_journals')
                        ->where('source_type', 'payment')
                        ->where('source_id', $payment->id)
                        ->first();

                    if ($journal && $journal->status !== 'cancelled') {
                        DB::table('acc_journals')
                            ->where('id', $journal->id)
                            ->update([
                                'status' => 'cancelled',
                                'notes'  => trim(($journal->notes ? $journal->notes . ' | ' : '') . 'إلغاء تلقائي: دفعة الكوتش لا تدخل في صندوق النادي'),
                                'updated_at' => now(),
                            ]);
                    }
                }

                // 2. Set safe_id = null on the coach payment
                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update([
                        'safe_id' => null,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Safe restoration if needed
    }
};
