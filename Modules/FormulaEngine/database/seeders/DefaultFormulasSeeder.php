<?php

namespace Modules\FormulaEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\FormulaEngine\Models\Formula;

class DefaultFormulasSeeder extends Seeder
{
    /**
     * Seed the default system formulas.
     * These are marked as is_system=true and cannot be deleted or modified by admins.
     */
    public function run(): void
    {
        $formulas = [

            // ── BMI: مؤشر كتلة الجسم ─────────────────────────────────────
            [
                'name'        => 'مؤشر كتلة الجسم (BMI)',
                'key'         => 'bmi',
                'expression'  => 'weight / pow(height / 100, 2)',
                'description' => 'Body Mass Index — يُحسب بقسمة الوزن (كجم) على مربع الطول (م)',
                'category'    => 'measurement',
                'return_type' => 'float',
                'unit'        => 'kg/m²',
                'is_system'   => true,
                'variables'   => [
                    ['variable_name' => 'weight', 'source_type' => 'measurement', 'db_column' => 'weight',  'is_required' => true],
                    ['variable_name' => 'height', 'source_type' => 'measurement', 'db_column' => 'height',  'is_required' => true],
                ],
            ],

            // ── BMR: معدل الأيض الأساسي (Mifflin-St Jeor للرجال) ──────────
            [
                'name'        => 'معدل الأيض الأساسي (BMR)',
                'key'         => 'bmr',
                'expression'  => '88.362 + (13.397 * weight) + (4.799 * height) - (5.677 * age)',
                'description' => 'Basal Metabolic Rate — معدل الحرق اليومي في حالة الراحة التامة (للرجال - Mifflin-St Jeor)',
                'category'    => 'measurement',
                'return_type' => 'float',
                'unit'        => 'kcal/day',
                'is_system'   => true,
                'variables'   => [
                    ['variable_name' => 'weight', 'source_type' => 'measurement', 'db_column' => 'weight',  'is_required' => true],
                    ['variable_name' => 'height', 'source_type' => 'measurement', 'db_column' => 'height',  'is_required' => true],
                    ['variable_name' => 'age',    'source_type' => 'input',                                  'is_required' => true],
                ],
            ],

            // ── TDEE: إجمالي السعرات اليومية (يعتمد على BMR) ─────────────
            [
                'name'        => 'إجمالي السعرات اليومية (TDEE)',
                'key'         => 'tdee',
                'expression'  => 'bmr * activity_factor',
                'description' => 'Total Daily Energy Expenditure — يُحسب بضرب BMR في معامل النشاط البدني',
                'category'    => 'measurement',
                'return_type' => 'float',
                'unit'        => 'kcal/day',
                'is_system'   => true,
                'variables'   => [
                    // bmr يُحسب تلقائياً من معادلة bmr (computed)
                    ['variable_name' => 'bmr',             'source_type' => 'computed',     'computed_formula_key' => 'bmr', 'is_required' => true],
                    // activity_factor يأتي من المدخل المباشر
                    ['variable_name' => 'activity_factor', 'source_type' => 'input',                                         'is_required' => true],
                ],
            ],

            // ── BFP: نسبة الدهون (طريقة البحرية الأمريكية — رجال) ─────────
            [
                'name'        => 'نسبة الدهون في الجسم (BFP — رجال)',
                'key'         => 'bfp_male',
                'expression'  => '86.010 * log10(waist - neck) - 70.041 * log10(height) + 36.76',
                'description' => 'Body Fat Percentage — طريقة البحرية الأمريكية للرجال (تعتمد على محيط الخصر والرقبة والطول)',
                'category'    => 'measurement',
                'return_type' => 'float',
                'unit'        => '%',
                'is_system'   => true,
                'variables'   => [
                    ['variable_name' => 'waist',  'source_type' => 'measurement', 'db_column' => 'waist_circumference',  'is_required' => true],
                    ['variable_name' => 'neck',   'source_type' => 'measurement', 'db_column' => 'neck_circumference',   'is_required' => true],
                    ['variable_name' => 'height', 'source_type' => 'measurement', 'db_column' => 'height',               'is_required' => true],
                ],
            ],

            // ── Ideal Weight: الوزن المثالي (معادلة Broca المعدّلة) ────────
            [
                'name'        => 'الوزن المثالي',
                'key'         => 'ideal_weight',
                'expression'  => 'height - 100 - ((height - 150) / 4)',
                'description' => 'الوزن المثالي بالكيلوغرام بناءً على الطول — معادلة Broca المعدّلة',
                'category'    => 'measurement',
                'return_type' => 'float',
                'unit'        => 'kg',
                'is_system'   => true,
                'variables'   => [
                    ['variable_name' => 'height', 'source_type' => 'measurement', 'db_column' => 'height', 'is_required' => true],
                ],
            ],

        ];

        foreach ($formulas as $formulaData) {
            $variables = $formulaData['variables'] ?? [];
            unset($formulaData['variables']);

            // Use updateOrCreate to avoid duplicates on re-seeding
            $formula = Formula::updateOrCreate(
                ['key' => $formulaData['key']],
                array_merge($formulaData, ['is_active' => true])
            );

            // Sync variables
            $formula->variables()->delete();
            foreach ($variables as $var) {
                $formula->variables()->create($var);
            }
        }

        $this->command->info('✅ Default formulas seeded: BMI, BMR, TDEE, BFP (Male), Ideal Weight');
    }
}
