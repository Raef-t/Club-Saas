<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * تحويل حقل name من JSON (ar/en) إلى نص عربي مباشر.
     * الترتيب الصحيح: تغيير نوع العمود أولاً → ثم تحديث البيانات.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $tables = [
            'subscription_plans' => 150,
            'activities'         => 150,
            'activity_types'     => 255,
            'branches'           => 255,
        ];

        foreach ($tables as $table => $length) {
            // 1. حذف أي CHECK CONSTRAINT موجود على عمود name
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND CONSTRAINT_TYPE = 'CHECK'
            ", [$table]);

            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$constraint->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) { /* تجاهل إذا لم يكن موجوداً */ }
            }

            // 2. تغيير نوع العمود من JSON إلى VARCHAR أولاً (قبل تحديث البيانات)
            DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `name` VARCHAR({$length}) NOT NULL");

            // 3. الآن نستطيع تحديث البيانات بأمان (لا JSON validation)
            $rows = DB::table($table)->get(['id', 'name']);
            foreach ($rows as $row) {
                $name = $this->extractArabic($row->name);
                if ($name !== $row->name) {
                    DB::table($table)->where('id', $row->id)->update(['name' => $name]);
                }
            }
        }
    }

    /**
     * استخراج القيمة العربية من JSON أو إعادة النص كما هو
     */
    private function extractArabic(?string $value): string
    {
        if (!$value) return '';

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded['ar'] ?? $decoded['en'] ?? $value;
        }

        return $value;
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // التراجع: إعادة العمود إلى JSON (البيانات ستبقى نصاً عادياً)
        DB::statement('ALTER TABLE `subscription_plans` MODIFY COLUMN `name` JSON NOT NULL');
        DB::statement('ALTER TABLE `activities` MODIFY COLUMN `name` JSON NOT NULL');
        DB::statement('ALTER TABLE `activity_types` MODIFY COLUMN `name` JSON NOT NULL');
        DB::statement('ALTER TABLE `branches` MODIFY COLUMN `name` JSON NOT NULL');
    }
};

