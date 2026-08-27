<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\AccAccount;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets (الأصول)
            ['code' => '1000', 'name' => 'الأصول', 'name_en' => 'Assets', 'type' => 'asset', 'currency' => 'BOTH', 'parent_code' => null, 'allow_manual_entry' => false],
            ['code' => '1100', 'name' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'type' => 'asset', 'currency' => 'BOTH', 'parent_code' => '1000', 'allow_manual_entry' => false],
            ['code' => '1101', 'name' => 'الصندوق النقدي - دولار', 'name_en' => 'Cash USD', 'type' => 'asset', 'currency' => 'USD', 'parent_code' => '1100', 'allow_manual_entry' => true],
            ['code' => '1102', 'name' => 'الصندوق النقدي - ليرة سورية', 'name_en' => 'Cash SYP', 'type' => 'asset', 'currency' => 'SYP', 'parent_code' => '1100', 'allow_manual_entry' => true],
            ['code' => '1201', 'name' => 'المصرف / البنك', 'name_en' => 'Bank', 'type' => 'asset', 'currency' => 'BOTH', 'parent_code' => '1100', 'allow_manual_entry' => true],
            ['code' => '1301', 'name' => 'الذمم المدينة', 'name_en' => 'Accounts Receivable', 'type' => 'asset', 'currency' => 'BOTH', 'parent_code' => '1100', 'allow_manual_entry' => true],
            ['code' => '1401', 'name' => 'مصاريف مدفوعة مقدماً', 'name_en' => 'Prepaid Expenses', 'type' => 'asset', 'currency' => 'BOTH', 'parent_code' => '1100', 'allow_manual_entry' => true],
            // Liabilities (الخصوم)
            ['code' => '2000', 'name' => 'الخصوم', 'name_en' => 'Liabilities', 'type' => 'liability', 'currency' => 'BOTH', 'parent_code' => null, 'allow_manual_entry' => false],
            ['code' => '2100', 'name' => 'الذمم الدائنة', 'name_en' => 'Accounts Payable', 'type' => 'liability', 'currency' => 'BOTH', 'parent_code' => '2000', 'allow_manual_entry' => true],
            ['code' => '2200', 'name' => 'قروض طويلة الأجل', 'name_en' => 'Long-term Loans', 'type' => 'liability', 'currency' => 'BOTH', 'parent_code' => '2000', 'allow_manual_entry' => true],
            ['code' => '2300', 'name' => 'مطلوبات أخرى', 'name_en' => 'Other Liabilities', 'type' => 'liability', 'currency' => 'BOTH', 'parent_code' => '2000', 'allow_manual_entry' => true],
            // Equity (حقوق الملكية)
            ['code' => '3000', 'name' => 'حقوق الملكية', 'name_en' => 'Equity', 'type' => 'equity', 'currency' => 'BOTH', 'parent_code' => null, 'allow_manual_entry' => false],
            ['code' => '3100', 'name' => 'رأس المال', 'name_en' => 'Capital', 'type' => 'equity', 'currency' => 'BOTH', 'parent_code' => '3000', 'allow_manual_entry' => true],
            ['code' => '3200', 'name' => 'الأرباح المحتجزة', 'name_en' => 'Retained Earnings', 'type' => 'equity', 'currency' => 'BOTH', 'parent_code' => '3000', 'allow_manual_entry' => true],
            ['code' => '3300', 'name' => 'المسحوبات الشخصية', 'name_en' => 'Drawings', 'type' => 'equity', 'currency' => 'BOTH', 'parent_code' => '3000', 'allow_manual_entry' => true],
            // Revenue (الإيرادات)
            ['code' => '4000', 'name' => 'الإيرادات', 'name_en' => 'Revenue', 'type' => 'revenue', 'currency' => 'BOTH', 'parent_code' => null, 'allow_manual_entry' => false],
            ['code' => '4100', 'name' => 'إيرادات الخدمات والاشتراكات', 'name_en' => 'Service Revenue', 'type' => 'revenue', 'currency' => 'BOTH', 'parent_code' => '4000', 'allow_manual_entry' => true],
            ['code' => '4200', 'name' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'type' => 'revenue', 'currency' => 'BOTH', 'parent_code' => '4000', 'allow_manual_entry' => true],
            // Expenses (المصاريف)
            ['code' => '5000', 'name' => 'المصاريف', 'name_en' => 'Expenses', 'type' => 'expense', 'currency' => 'BOTH', 'parent_code' => null, 'allow_manual_entry' => false],
            ['code' => '5100', 'name' => 'رواتب وأجور', 'name_en' => 'Salaries & Wages', 'type' => 'expense', 'currency' => 'BOTH', 'parent_code' => '5000', 'allow_manual_entry' => true],
            ['code' => '5200', 'name' => 'إيجارات', 'name_en' => 'Rent', 'type' => 'expense', 'currency' => 'BOTH', 'parent_code' => '5000', 'allow_manual_entry' => true],
            ['code' => '5300', 'name' => 'مصاريف إدارية', 'name_en' => 'Administrative Expenses', 'type' => 'expense', 'currency' => 'BOTH', 'parent_code' => '5000', 'allow_manual_entry' => true],
            ['code' => '5400', 'name' => 'مصاريف تشغيلية', 'name_en' => 'Operating Expenses', 'type' => 'expense', 'currency' => 'BOTH', 'parent_code' => '5000', 'allow_manual_entry' => true],
            ['code' => '5500', 'name' => 'مصاريف أخرى', 'name_en' => 'Other Expenses', 'type' => 'expense', 'currency' => 'BOTH', 'parent_code' => '5000', 'allow_manual_entry' => true],
        ];

        foreach ($accounts as $data) {
            $parentCode = $data['parent_code'];
            unset($data['parent_code']);
            $parentId = $parentCode ? AccAccount::where('code', $parentCode)->first()?->id : null;
            $data['parent_id'] = $parentId;

            AccAccount::updateOrCreate(['code' => $data['code']], $data);
        }

        // Seed default safes and settings for existing branches
        foreach (\Modules\ClubManager\Models\Branch::all() as $branch) {
            // Create a USD Safe
            $usdSafe = \Modules\Accounting\Models\AccSafe::firstOrCreate([
                'name' => "صندوق دولار - " . $branch->name,
                'branch_id' => $branch->id,
            ], [
                'account_id' => AccAccount::where('code', '1101')->first()?->id,
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'صندوق دولار افتراضي للفرع',
            ]);

            // Create a SYP Safe
            $sypSafe = \Modules\Accounting\Models\AccSafe::firstOrCreate([
                'name' => "صندوق ليرة - " . $branch->name,
                'branch_id' => $branch->id,
            ], [
                'account_id' => AccAccount::where('code', '1102')->first()?->id,
                'currency' => 'SYP',
                'is_active' => true,
                'notes' => 'صندوق ليرة افتراضي للفرع',
            ]);

            // Set up branch setting
            \Modules\Accounting\Models\AccBranchSetting::firstOrCreate([
                'branch_id' => $branch->id,
            ], [
                'default_safe_id' => $sypSafe->id,
                'cash_usd_account_code' => '1101',
                'cash_syp_account_code' => '1102',
                'revenue_account_code' => '4100',
                'expense_account_code' => '5300',
                'supported_currencies' => ['SYP', 'USD'],
            ]);
        }

        $this->command->info('✅ تم زرع شجرة الحسابات الافتراضية بنجاح');
    }
}
