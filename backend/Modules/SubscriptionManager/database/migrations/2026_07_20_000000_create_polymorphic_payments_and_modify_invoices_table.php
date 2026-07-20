<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to payments
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('id');
            $table->enum('type', ['in', 'out'])->default('in')->after('amount');
            $table->nullableMorphs('payable'); // creates payable_type, payable_id
        });

        // 2. Migrate existing data
        $payments = DB::table('payments')->whereNotNull('invoice_id')->get();
        foreach ($payments as $payment) {
            DB::table('payments')->where('id', $payment->id)->update([
                'payable_type' => 'Modules\SubscriptionManager\Models\Invoice',
                'payable_id'   => $payment->invoice_id,
                'type'         => 'in'
            ]);
        }

        // 3. Drop invoice_id constraint and column
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        // 4. Drop code from invoices
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'code')) {
                $table->dropColumn('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable();
        });

        $payments = DB::table('payments')->where('payable_type', 'Modules\SubscriptionManager\Models\Invoice')->get();
        foreach ($payments as $payment) {
            DB::table('payments')->where('id', $payment->id)->update([
                'invoice_id' => $payment->payable_id
            ]);
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->dropColumn('receipt_number');
            $table->dropColumn('type');
            $table->dropMorphs('payable');
        });
    }
};
