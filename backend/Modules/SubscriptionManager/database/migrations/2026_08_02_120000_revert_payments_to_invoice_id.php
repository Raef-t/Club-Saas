<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            // 1. Add invoice_id column if it doesn't exist
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'invoice_id')) {
                    $table->unsignedBigInteger('invoice_id')->nullable()->after('id');
                }
            });

            // 2. Safely preserve & migrate existing data from payable_id to invoice_id
            DB::transaction(function () {
                if (Schema::hasColumn('payments', 'payable_id')) {
                    DB::table('payments')
                        ->whereNotNull('payable_id')
                        ->where(function ($query) {
                            $query->whereNull('invoice_id')
                                  ->orWhere('invoice_id', 0);
                        })
                        ->update([
                            'invoice_id' => DB::raw('payable_id')
                        ]);
                }
            });

            // 3. Foreign key and cleanup polymorphic columns
            Schema::table('payments', function (Blueprint $table) {
                // Drop morphs if present
                if (Schema::hasColumn('payments', 'payable_type') && Schema::hasColumn('payments', 'payable_id')) {
                    $table->dropMorphs('payable');
                }

                if (Schema::hasColumn('payments', 'type')) {
                    $table->dropColumn('type');
                }

                if (Schema::hasColumn('payments', 'receipt_number')) {
                    $table->dropColumn('receipt_number');
                }

                // Add foreign key for invoice_id if not already added
                $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('receipt_number')->nullable()->after('id');
                $table->enum('type', ['in', 'out'])->default('in')->after('amount');
                $table->nullableMorphs('payable');
            });

            DB::table('payments')->whereNotNull('invoice_id')->update([
                'payable_type' => 'Modules\SubscriptionManager\Models\Invoice',
                'payable_id'   => DB::raw('invoice_id'),
                'type'         => 'in'
            ]);

            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            });
        }
    }
};
