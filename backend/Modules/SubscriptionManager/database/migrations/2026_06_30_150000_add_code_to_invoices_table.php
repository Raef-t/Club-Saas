<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\SubscriptionManager\Models\Invoice;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        // Generate codes for existing invoices
        \Illuminate\Support\Facades\DB::table('invoices')->whereNull('code')->orderBy('id')->chunkById(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                $code = 'INV_' . str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
                while (\Illuminate\Support\Facades\DB::table('invoices')->where('code', $code)->exists()) {
                    $code = 'INV_' . str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
                }
                \Illuminate\Support\Facades\DB::table('invoices')->where('id', $invoice->id)->update(['code' => $code]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
