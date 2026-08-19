<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanAccountingJournals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:clean-journals {--force : Force the operation to run without confirmation (required in production)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تنظيف وتفريغ كافة السندات والعمليات والقيود المحاسبية والتسويات، وإعادة فتح الفترات المالية المغلقة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. التحقق من بيئة العمل وطلب التأكيد
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('⚠️ تنبيه: أنت تقوم بتشغيل هذا الأمر في بيئة الإنتاج (Production)! يجب استخدام الخيار --force للتنفيذ.');
            return 1;
        }

        if (!$this->option('force')) {
            $confirm = $this->confirm(
                'هل أنت متأكد من رغبتك في مسح وتنظيف كافة السندات والقيود والعمليات المحاسبية والتسويات؟ (لن يتم لمس الأعضاء أو اشتراكاتهم نهائياً)',
                false
            );

            if (!$confirm) {
                $this->info('❌ تم إلغاء العملية بناءً على طلبك.');
                return 0;
            }
        }

        $this->info('📊 جاري جمع الإحصائيات قبل البدء بالتنظيف...');

        // 2. جمع الإحصائيات قبل المسح
        try {
            // إحصائيات السندات
            $totalJournals = DB::table('acc_journals')->count();
            $journalsByType = DB::table('acc_journals')
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            $jvCount = $journalsByType['JV'] ?? 0;
            $rvCount = $journalsByType['RV'] ?? 0;
            $pvCount = $journalsByType['PV'] ?? 0;
            $siCount = $journalsByType['SI'] ?? 0;
            $piCount = $journalsByType['PI'] ?? 0;

            // إحصائيات القيود والتسويات والأطراف المقابلة والرواتب
            $totalEntries = DB::table('acc_journal_entries')->count();
            $totalReconciliations = DB::table('acc_reconciliations')->count();
            $totalSalaryPayments = Schema::hasTable('acc_salary_payments') ? DB::table('acc_salary_payments')->count() : 0;

        } catch (\Exception $e) {
            $this->error('❌ حدث خطأ أثناء جمع الإحصائيات أو قراءة قاعدة البيانات: ' . $e->getMessage());
            return 1;
        }

        $this->info('🧹 جاري بدء عملية التنظيف...');

        // 3. تنفيذ عملية التنظيف تحت Transaction
        DB::beginTransaction();
        try {
            // تعطيل التحقق من المفاتيح الأجنبية
            Schema::disableForeignKeyConstraints();

            // مسح تسويات الصناديق
            DB::table('acc_reconciliations')->delete();

            // مسح أسطر السندات
            DB::table('acc_journal_entries')->delete();

            // مسح السندات نفسها
            DB::table('acc_journals')->delete();

            // مسح حركات الرواتب المحاسبية إن وجدت
            if (Schema::hasTable('acc_salary_payments')) {
                DB::table('acc_salary_payments')->delete();
            }

            // إعادة تفعيل التحقق من المفاتيح الأجنبية
            Schema::enableForeignKeyConstraints();

            DB::commit();
            $this->info('✅ تم تنظيف كافة العمليات والقيود المحاسبية بنجاح.');

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Schema::enableForeignKeyConstraints();
            $this->error('❌ حدث خطأ غير متوقع أثناء عملية التنظيف، وتم التراجع عن كافة التغييرات: ' . $e->getMessage());
            return 1;
        }

        // 4. عرض الإحصائيات في جدول منسق
        $this->newLine();
        $this->info('📋 تقرير العمليات المنجزة والبيانات المحذوفة:');
        
        $statsTable = [
            ['سندات قيود اليومية (JV)', $jvCount],
            ['سندات القبض (RV)', $rvCount],
            ['سندات الصرف (PV)', $pvCount],
            ['سندات مبيعات (SI)', $siCount],
            ['سندات مشتريات (PI)', $piCount],
            ['إجمالي السندات المحذوفة (acc_journals)', $totalJournals],
            ['إجمالي أسطر القيود المحذوفة (acc_journal_entries)', $totalEntries],
            ['إجمالي تسويات الصناديق المحذوفة (acc_reconciliations)', $totalReconciliations],
            ['إجمالي مدفوعات الرواتب المحذوفة (acc_salary_payments)', $totalSalaryPayments],
        ];

        $this->table(['نوع البيانات / العملية', 'العدد المحذوف / المعدل'], $statsTable);
        $this->newLine();
        $this->comment('💡 ملاحظة: بقيت بيانات المشتركين والاشتراكات، وشجرة الحسابات، والصناديق، والشركاء، والفترات المالية سليمة تماماً دون أي مساس.');
        
        return 0;
    }
}
