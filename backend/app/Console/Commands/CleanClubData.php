<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanClubData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club:clean-data 
                            {--dry-run : معاينة التغييرات والبيانات المستهدفة دون تنفيذ أي مسح}
                            {--force : تنفيذ العملية فوراً دون طلب تأكيد تفاعلي}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تنظيف قاعدة البيانات من المشتركين والاشتراكات والرواتب وسجلات الحضور وحسابات المستخدمين ماعدا الإدارة والكوتشات';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->newLine();
        $this->info('===============================================================');
        $this->info('         🏢 نظام إدارة النوادي - تفريغ وتنظيف قاعدة البيانات        ');
        $this->info('===============================================================');
        $this->newLine();

        if ($isDryRun) {
            $this->comment('🔍 وضع المعاينة التجريبي (Dry Run) مُفعل - لن يتم إجراء أي تعديل على قاعدة البيانات.');
            $this->newLine();
        }

        // 1. تحديد المعرفات المستهدفة للإبقاء والحذف
        $this->info('📊 جاري فحص وتحليل بيانات النظام...');

        // استخراج معرفات الأشخاص للإدارة والكوتشات
        $adminPersonIds = DB::table('people')->where('type', 'admin')->pluck('id')->toArray();
        $adminStaffPersonIds = DB::table('staff')->where('role', 'admin')->pluck('person_id')->toArray();
        $coachStaffPersonIds = DB::table('staff')->where('role', 'coach')->pluck('person_id')->toArray();

        $preservedPersonIds = array_values(array_unique(array_merge($adminPersonIds, $adminStaffPersonIds, $coachStaffPersonIds)));
        sort($preservedPersonIds);

        // الأشخاص المحذوفون (مشتركين + موظفي استقبال + عمال نظافة)
        $allPersonIds = DB::table('people')->pluck('id')->toArray();
        $deletedPersonIds = array_values(array_diff($allPersonIds, $preservedPersonIds));

        // حسابات المستخدمين
        $preservedAuthUserIds = DB::table('authentication_users')->whereIn('person_id', $preservedPersonIds)->pluck('id')->toArray();
        $deletedAuthUserIds = DB::table('authentication_users')->whereIn('person_id', $deletedPersonIds)->pluck('id')->toArray();

        // طاقم العمل المحذوف (استقبال + نظافة)
        $deletedStaffIds = DB::table('staff')->whereIn('person_id', $deletedPersonIds)->pluck('id')->toArray();
        $preservedStaffIds = DB::table('staff')->whereIn('person_id', $preservedPersonIds)->pluck('id')->toArray();

        // إحصائيات الجداول قبل الحذف
        $stats = [
            'المشتركين (members)' => DB::table('members')->count(),
            'اشتراكات اللاعبين (player_subscriptions)' => DB::table('player_subscriptions')->count(),
            'بنود اشتراكات اللاعبين (player_subscription_items)' => DB::table('player_subscription_items')->count(),
            'تجميد الاشتراكات (subscription_freezes)' => DB::table('subscription_freezes')->count(),
            'توزيع إيرادات الاشتراكات (subscription_revenue_splits)' => DB::table('subscription_revenue_splits')->count(),
            'سجلات الحضور (attendances)' => DB::table('attendances')->count(),
            'استهلاك حصص الحضور (attendance_consumptions)' => DB::table('attendance_consumptions')->count(),
            'مسيرات الرواتب (payroll_runs)' => DB::table('payroll_runs')->count(),
            'قسائم الرواتب (payslips)' => DB::table('payslips')->count(),
            'تعديلات الرواتب (payslip_adjustments)' => DB::table('payslip_adjustments')->count(),
            'الفواتير (invoices)' => DB::table('invoices')->count(),
            'المدفوعات وسندات القبض (payments)' => DB::table('payments')->count(),
            'السندات المحاسبية (acc_journals)' => DB::table('acc_journals')->count(),
            'أسطر القيود المحاسبية (acc_journal_entries)' => DB::table('acc_journal_entries')->count(),
            'حجوزات الخزائن (locker_reservations)' => DB::table('locker_reservations')->count(),
            'سجلات الأشخاص المحذوفة (people - مشتركون واستقبال ونظافة)' => count($deletedPersonIds),
            'حسابات الدخول المحذوفة (authentication_users)' => count($deletedAuthUserIds),
            'سجلات طاقم العمل المحذوفة (staff - استقبال ونظافة)' => count($deletedStaffIds),
            'سجل حركات النظام (activity_log)' => DB::table('activity_log')->count(),
            'الإشعارات والمستلمين (notifications & recipients)' => DB::table('notifications')->count() + DB::table('notification_recipients')->count(),
            'أجهزة وتوكنز المستخدمين (user_devices & tokens)' => DB::table('user_devices')->count() + DB::table('personal_access_tokens')->count(),
        ];

        $preservedStats = [
            'الأندية والفروع (clubs & branches)' => DB::table('clubs')->count() . ' نادي / ' . DB::table('branches')->count() . ' فرع',
            'خطط الاشتراكات (subscription_plans)' => DB::table('subscription_plans')->count(),
            'جدول الحصص الرياضية (sport_session_templates)' => DB::table('sport_session_templates')->count(),
            'الأنشطة والمرافق (activities & facilities)' => DB::table('activities')->count() . ' نشاط / ' . DB::table('facilities')->count() . ' مرفق',
            'شجرة الحسابات والصناديق (acc_accounts & safes)' => DB::table('acc_accounts')->count() . ' حساب / ' . DB::table('acc_safes')->count() . ' صندوق',
            'الكوتشات المحفوظون (Coaches)' => DB::table('staff')->where('role', 'coach')->count(),
            'الإداريون المحفوظون (Admins & Super Admin)' => count($adminStaffPersonIds) + (in_array(1, $adminPersonIds) ? 1 : 0),
            'حسابات الدخول المحفوظة (Preserved Auth Users)' => count($preservedAuthUserIds),
            'الخزائن (lockers - ستُعاد لحالة متاحة)' => DB::table('lockers')->count(),
        ];

        // عرض جدول البيانات المحذوفة
        $this->info('📋 ملخص السجلات التي سيتم مسحها وتصفيرها:');
        $deleteRows = [];
        foreach ($stats as $entity => $count) {
            $deleteRows[] = [$entity, $count];
        }
        $this->table(['البيان / الجدول', 'العدد المستهدف للحذف'], $deleteRows);
        $this->newLine();

        // عرض جدول البيانات المحفوظة
        $this->info('🛡️ ملخص البيانات التي سيتم الحفاظ عليها بالكامل دون مساس:');
        $preserveRows = [];
        foreach ($preservedStats as $entity => $val) {
            $preserveRows[] = [$entity, $val];
        }
        $this->table(['البيان / الفئة', 'العدد / الحالة'], $preserveRows);
        $this->newLine();

        if ($isDryRun) {
            $this->comment('✨ انتهت المعاينة التجريبية. لتنفيذ التنظيف الفعلي شغّل الأمر بدون --dry-run:');
            $this->comment('👉 php artisan club:clean-data');
            return 0;
        }

        // طلب التأكيد في حال عدم استخدام force
        if (!$force) {
            $confirm = $this->confirm(
                '⚠️ هل أنت متأكد تماماً من رغبتك في تنفيذ عملية التنظيف وحذف السجلات الموضحة أعلاه نهائياً؟',
                false
            );

            if (!$confirm) {
                $this->warn('❌ تم إلغاء العملية بناءً على طلبك.');
                return 0;
            }
        }

        // أخذ نسخة احتياطية أولاً قبل البدء
        $this->info('💾 جاري إنشاء نسخة احتياطية من قاعدة البيانات قبل البدء...');
        $backupDir = storage_path('backups');
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $backupFile = $backupDir . '/backup_auto_' . date('Ymd_His') . '.sql';
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbName = config('database.connections.mysql.database', 'db_clubs');
        $dbUser = config('database.connections.mysql.username', 'root');
        $dbPass = config('database.connections.mysql.password', '');

        $passArg = $dbPass !== '' ? "-p" . escapeshellarg($dbPass) : "";
        $dumpCmd = sprintf(
            "mysqldump -h %s -u %s %s %s > %s 2>/dev/null",
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        @exec($dumpCmd, $output, $returnVar);
        if ($returnVar === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            $this->info('✅ تم حفظ النسخة الاحتياطية بنجاح في: ' . $backupFile);
        } else {
            $this->warn('⚠️ تنبيه: لم يتم إنشاء النسخة الاحتياطية تلقائياً، ولكن جاري الاستمرار في العملية.');
        }

        // تنفيذ عملية التنظيف تحت Transaction
        $this->newLine();
        $this->info('🚀 جاري بدء عملية التنظيف الشامل...');

        DB::beginTransaction();
        try {
            Schema::disableForeignKeyConstraints();

            // 1. مسح الحضور واستهلاك الحصص
            DB::table('attendance_consumptions')->delete();
            DB::table('attendances')->delete();
            if (Schema::hasTable('qr_access_logs')) {
                DB::table('qr_access_logs')->delete();
            }

            // 2. مسح الاشتراكات وتجميداتها وتوزيع الإيرادات
            DB::table('subscription_freezes')->delete();
            DB::table('player_subscription_items')->delete();
            DB::table('subscription_revenue_splits')->delete();

            // 3. مسح المدفوعات والفواتير والقيود المحاسبية والتسويات
            if (Schema::hasTable('acc_reconciliations')) {
                DB::table('acc_reconciliations')->delete();
            }
            DB::table('acc_journal_entries')->delete();
            DB::table('acc_journals')->delete();
            if (Schema::hasTable('acc_salary_payments')) {
                DB::table('acc_salary_payments')->delete();
            }
            DB::table('payments')->delete();
            DB::table('invoices')->delete();
            DB::table('player_subscriptions')->delete();

            // 4. مسح بيانات الأعضاء والتقييمات والملفات الصحية
            if (Schema::hasTable('member_evaluations')) {
                DB::table('member_evaluations')->delete();
            }
            if (Schema::hasTable('member_health_profiles')) {
                DB::table('member_health_profiles')->delete();
            }
            if (Schema::hasTable('member_measurements')) {
                DB::table('member_measurements')->delete();
            }
            DB::table('members')->delete();

            // 5. مسح حجوزات الخزائن وإعادة تعيين حالة الخزائن
            DB::table('locker_reservations')->delete();
            DB::table('lockers')->whereNotIn('status', ['maintenance'])->update([
                'status' => 'available',
                'updated_at' => now(),
            ]);

            // 6. مسح الرواتب وقسائم الرواتب وتعديلاتها
            DB::table('payslip_adjustments')->delete();
            DB::table('payslips')->delete();
            DB::table('payroll_runs')->delete();

            // 7. مسح إشعارات ومحفظة وحركات المستخدمين
            if (Schema::hasTable('notification_attachments')) {
                DB::table('notification_attachments')->delete();
            }
            DB::table('notification_recipients')->delete();
            DB::table('notifications')->delete();
            DB::table('activity_log')->delete();
            if (Schema::hasTable('command_executions')) {
                DB::table('command_executions')->delete();
            }
            if (Schema::hasTable('wallet_transactions')) {
                DB::table('wallet_transactions')->delete();
            }
            if (Schema::hasTable('wallets')) {
                DB::table('wallets')->whereIn('person_id', $deletedPersonIds)->delete();
            }

            // 8. مسح أجهزة الدخول والتوكنز وأدوار المستخدمين المحذوفين
            DB::table('user_devices')->delete();
            DB::table('personal_access_tokens')->delete();

            if (!empty($deletedAuthUserIds)) {
                DB::table('model_has_roles')
                    ->whereIn('model_id', $deletedAuthUserIds)
                    ->where('model_type', 'like', '%Auth%')
                    ->delete();

                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')
                        ->whereIn('model_id', $deletedAuthUserIds)
                        ->where('model_type', 'like', '%Auth%')
                        ->delete();
                }

                DB::table('authentication_users')->whereIn('id', $deletedAuthUserIds)->delete();
            }

            // 9. مسح تفاصيل طاقم العمل المحذوف (استقبال ونظافة)
            if (!empty($deletedStaffIds)) {
                DB::table('staff_contracts')->whereIn('staff_id', $deletedStaffIds)->delete();
                DB::table('staff_shifts')->whereIn('staff_id', $deletedStaffIds)->delete();
                DB::table('staff_branches')->whereIn('staff_id', $deletedStaffIds)->delete();
                DB::table('staff_activities')->whereIn('staff_id', $deletedStaffIds)->delete();
                if (Schema::hasTable('staff_commission_rules')) {
                    DB::table('staff_commission_rules')->whereIn('staff_id', $deletedStaffIds)->delete();
                }
                DB::table('staff')->whereIn('id', $deletedStaffIds)->delete();
            }

            // 10. مسح جهات الاتصال وباركودات الأشخاص وسجلات people المحذوفة
            if (!empty($deletedPersonIds)) {
                DB::table('person_contacts')->whereIn('person_id', $deletedPersonIds)->delete();
                DB::table('person_qr_codes')->whereIn('person_id', $deletedPersonIds)->delete();
                DB::table('people')->whereIn('id', $deletedPersonIds)->delete();
            }

            Schema::enableForeignKeyConstraints();
            DB::commit();

            $this->info('✅ تم تنفيذ عملية الحذف والتنظيف بنجاح تام داخل Transaction.');

        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Schema::enableForeignKeyConstraints();
            $this->error('❌ حدث خطأ أثناء عملية التنظيف، وتم التراجع عن كافة التغييرات: ' . $e->getMessage());
            return 1;
        }

        // مسح الكاش
        $this->info('🧹 جاري تفريغ الكاش والجلسات...');
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->delete();
            }
            $this->info('✅ تم تفريغ الكاش والجلسات بنجاح.');
        } catch (\Exception $e) {
            $this->warn('⚠️ تنبيه أثناء تفريغ الكاش: ' . $e->getMessage());
        }

        // عرض تقرير الإنجاز النهائي
        $this->newLine();
        $this->info('===============================================================');
        $this->info('                  🎉 اكتملت عملية التنظيف بنجاح!                 ');
        $this->info('===============================================================');
        $this->newLine();

        $finalStats = [
            ['المشتركين (members)', DB::table('members')->count()],
            ['اشتراكات اللاعبين (player_subscriptions)', DB::table('player_subscriptions')->count()],
            ['سجلات الحضور (attendances)', DB::table('attendances')->count()],
            ['الرواتب وقسائم الرواتب (payslips)', DB::table('payslips')->count()],
            ['الفواتير والمدفوعات (invoices & payments)', DB::table('invoices')->count() . ' / ' . DB::table('payments')->count()],
            ['السندات المحاسبية (acc_journals)', DB::table('acc_journals')->count()],
            ['حسابات المستخدمين المتبقية (الإدارة والكوتشات)', DB::table('authentication_users')->count()],
            ['طاقم العمل المتبقي (staff - الإدارة والكوتشات)', DB::table('staff')->count()],
            ['خطط الاشتراكات (subscription_plans)', DB::table('subscription_plans')->count()],
            ['شجرة الحسابات والخزن (accounts & safes)', DB::table('acc_accounts')->count() . ' / ' . DB::table('acc_safes')->count()],
            ['الخزائن المتاحة (lockers available)', DB::table('lockers')->where('status', 'available')->count()],
        ];

        $this->table(['البيان / الجدول', 'العدد المتبقي في قاعدة البيانات'], $finalStats);
        $this->newLine();
        $this->comment('💡 أصبحت قاعدة البيانات نظيفة وجاهزة تماماً لاستقبال المشتركين والاشتراكات الحقيقية.');

        return 0;
    }
}
