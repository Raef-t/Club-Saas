<?php

namespace Modules\MemberManager\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\MemberManager\Models\Member;
use Modules\MemberManager\Models\MemberMeasurement;

class MemberMeasurementReportService
{
    /**
     * Define metadata dictionary for all physical measurement fields.
     */
    public function getMetricsDefinition(): array
    {
        return [
            // 1. Body Composition (تكوين الجسم والكتل الحيوية)
            'weight' => [
                'label' => 'الوزن',
                'unit' => 'kg',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],
            'height' => [
                'label' => 'الطول',
                'unit' => 'cm',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],
            'bmi' => [
                'label' => 'مؤشر كتلة الجسم (BMI)',
                'unit' => 'kg/m²',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],
            'body_fat_percentage' => [
                'label' => 'نسبة الدهون',
                'unit' => '%',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],
            'muscle_mass' => [
                'label' => 'الكتلة العضلية',
                'unit' => 'kg',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],
            'fat_free_mass_percentage' => [
                'label' => 'نسبة الكتلة الخالية من الدهون',
                'unit' => '%',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],
            'body_water_percentage' => [
                'label' => 'نسبة السوائل والماء',
                'unit' => '%',
                'category' => 'body_composition',
                'category_label' => 'تكوين الجسم والكتل الحيوية',
                'is_numeric' => true,
            ],

            // 2. Metabolism & Energy (الأيض والطاقة)
            'resting_metabolic_rate' => [
                'label' => 'معدل الأيض الأساسي (BMR)',
                'unit' => 'kcal',
                'category' => 'metabolism_and_energy',
                'category_label' => 'الأيض والطاقة',
                'is_numeric' => true,
            ],
            'total_daily_energy_expenditure' => [
                'label' => 'احتياج السعرات اليومي (TDEE)',
                'unit' => 'kcal',
                'category' => 'metabolism_and_energy',
                'category_label' => 'الأيض والطاقة',
                'is_numeric' => true,
            ],
            'physical_activity_level' => [
                'label' => 'مستوى النشاط البدني',
                'unit' => '',
                'category' => 'metabolism_and_energy',
                'category_label' => 'الأيض والطاقة',
                'is_numeric' => false,
            ],

            // 3. Upper Body & Trunk (الجزء العلوي والجذع)
            'neck_circumference' => [
                'label' => 'محيط الرقبة',
                'unit' => 'cm',
                'category' => 'upper_body_and_trunk',
                'category_label' => 'الجزء العلوي والجذع',
                'is_numeric' => true,
            ],
            'shoulder_circumference' => [
                'label' => 'محيط الكتفين',
                'unit' => 'cm',
                'category' => 'upper_body_and_trunk',
                'category_label' => 'الجزء العلوي والجذع',
                'is_numeric' => true,
            ],
            'chest_circumference' => [
                'label' => 'محيط الصدر',
                'unit' => 'cm',
                'category' => 'upper_body_and_trunk',
                'category_label' => 'الجزء العلوي والجذع',
                'is_numeric' => true,
            ],
            'waist_circumference' => [
                'label' => 'محيط الخصر',
                'unit' => 'cm',
                'category' => 'upper_body_and_trunk',
                'category_label' => 'الجزء العلوي والجذع',
                'is_numeric' => true,
            ],
            'hip_circumference' => [
                'label' => 'محيط الحوض / الورك',
                'unit' => 'cm',
                'category' => 'upper_body_and_trunk',
                'category_label' => 'الجزء العلوي والجذع',
                'is_numeric' => true,
            ],
            'buttocks_circumference' => [
                'label' => 'محيط الأرداف',
                'unit' => 'cm',
                'category' => 'upper_body_and_trunk',
                'category_label' => 'الجزء العلوي والجذع',
                'is_numeric' => true,
            ],

            // 4. Arms (محيطات الذراعين)
            'right_bicep' => [
                'label' => 'محيط البايسبس الأيمن',
                'unit' => 'cm',
                'category' => 'arms',
                'category_label' => 'محيطات الذراعين',
                'is_numeric' => true,
            ],
            'left_bicep' => [
                'label' => 'محيط البايسبس الأيسر',
                'unit' => 'cm',
                'category' => 'arms',
                'category_label' => 'محيطات الذراعين',
                'is_numeric' => true,
            ],

            // 5. Lower Body & Legs (الجزء السفلي والساقين)
            'right_thigh_mid' => [
                'label' => 'منتصف الفخذ الأيمن',
                'unit' => 'cm',
                'category' => 'lower_body_and_legs',
                'category_label' => 'الجزء السفلي والساقين',
                'is_numeric' => true,
            ],
            'left_thigh' => [
                'label' => 'الفخذ الأيسر',
                'unit' => 'cm',
                'category' => 'lower_body_and_legs',
                'category_label' => 'الجزء السفلي والساقين',
                'is_numeric' => true,
            ],
            'above_right_knee' => [
                'label' => 'فوق الركبة اليمنى',
                'unit' => 'cm',
                'category' => 'lower_body_and_legs',
                'category_label' => 'الجزء السفلي والساقين',
                'is_numeric' => true,
            ],
            'above_left_knee' => [
                'label' => 'فوق الركبة اليسرى',
                'unit' => 'cm',
                'category' => 'lower_body_and_legs',
                'category_label' => 'الجزء السفلي والساقين',
                'is_numeric' => true,
            ],
            'right_calf' => [
                'label' => 'محيط الساق اليمنى',
                'unit' => 'cm',
                'category' => 'lower_body_and_legs',
                'category_label' => 'الجزء السفلي والساقين',
                'is_numeric' => true,
            ],
            'left_calf' => [
                'label' => 'محيط الساق اليسرى',
                'unit' => 'cm',
                'category' => 'lower_body_and_legs',
                'category_label' => 'الجزء السفلي والساقين',
                'is_numeric' => true,
            ],
        ];
    }

    /**
     * Map English month names to Arabic for consistent Arabic month labels.
     */
    protected function formatArabicMonthName(Carbon $date): string
    {
        $monthsAr = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        return ($monthsAr[$date->month] ?? $date->format('F')) . ' ' . $date->year;
    }

    /**
     * Generate comprehensive physical measurement report.
     * If dates are not provided, handles first-time records or compares the last two recorded measurements.
     */
    public function generateMonthlyReport(int $memberId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $member = Member::find($memberId);
        $memberName = $member ? ($member->name ?? $member->full_name ?? "Member #{$memberId}") : "Member #{$memberId}";

        // في حال عدم إرسال التواريخ: التعامل مع القياس الأول أو المقارنة بين آخر مرتين
        if (empty($fromDate) && empty($toDate)) {
            return $this->generateLastTwoMeasurementsReport($memberId, $memberName);
        }

        // في حال تم إرسال نطاق تواريخ محدد
        return $this->generateDateRangeReport($memberId, $memberName, $fromDate, $toDate);
    }

    /**
     * Generate comparison report between the last two recorded measurements or single initial record.
     */
    protected function generateLastTwoMeasurementsReport(int $memberId, string $memberName): array
    {
        $metricsDef = $this->getMetricsDefinition();

        // جلب آخر قياسين مسجلين للعضو
        $latestRecords = MemberMeasurement::where('member_id', $memberId)
            ->orderBy('measurement_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(2)
            ->get()
            ->reverse()
            ->values();

        $count = $latestRecords->count();
        $timelineKeys = [];
        $timelineLabels = [];
        $monthlyDetails = [];

        $metricValuesTimeSeries = [];
        foreach ($metricsDef as $fieldKey => $def) {
            $metricValuesTimeSeries[$fieldKey] = [];
        }

        // حالة 1: لا توجد قياسات نهائياً للعضو
        if ($count === 0) {
            $metricsByCategory = $this->buildMetricsByCategory($metricsDef, $metricValuesTimeSeries, 0);

            return [
                'member' => [
                    'id' => (int) $memberId,
                    'name' => $memberName,
                ],
                'filter' => [
                    'mode' => 'no_records',
                    'from_date' => null,
                    'to_date' => null,
                    'records_count' => 0,
                ],
                'months' => [],
                'months_labels' => [],
                'metrics_by_category' => $metricsByCategory,
                'monthly_details' => [],
            ];
        }

        // حالة 2 و 3: بناء البيانات لكل قياس (سواء قياس أول وحيد أو آخر قياسين)
        foreach ($latestRecords as $index => $record) {
            $dateStr = Carbon::parse($record->measurement_date)->toDateString();
            $carbonDate = Carbon::parse($record->measurement_date);

            if ($count === 1) {
                // حالة القياس الأول / الوحيد
                $label = "القياس الأولي الأساسي ({$carbonDate->format('d/m/Y')})";
            } else {
                // حالة وجود قياسين (المقارنة بين آخر مرتين)
                $labelPrefix = ($index === 0) ? 'القياس السابق' : 'القياس الأخير';
                $label = "{$labelPrefix} ({$carbonDate->format('d/m/Y')})";
            }

            $timelineKeys[] = $dateStr;
            $timelineLabels[] = $label;

            $measurementsSnapshot = [];
            foreach ($metricsDef as $fieldKey => $def) {
                $val = $record->{$fieldKey};
                if ($def['is_numeric'] && $val !== null) {
                    $val = (float) $val;
                }
                $measurementsSnapshot[$fieldKey] = $val;
                $metricValuesTimeSeries[$fieldKey][] = $val;
            }

            $monthlyDetails[] = [
                'month' => $dateStr,
                'month_label' => $label,
                'has_actual_record' => true,
                'record_date' => $dateStr,
                'carried_from_date' => null,
                'measurements' => $measurementsSnapshot,
            ];
        }

        $metricsByCategory = $this->buildMetricsByCategory($metricsDef, $metricValuesTimeSeries, $count);

        return [
            'member' => [
                'id' => (int) $memberId,
                'name' => $memberName,
            ],
            'filter' => [
                'mode' => ($count === 1) ? 'single_record' : 'last_two_records',
                'from_date' => Carbon::parse($latestRecords->first()->measurement_date)->toDateString(),
                'to_date' => Carbon::parse($latestRecords->last()->measurement_date)->toDateString(),
                'records_count' => $count,
            ],
            'months' => $timelineKeys,
            'months_labels' => $timelineLabels,
            'metrics_by_category' => $metricsByCategory,
            'monthly_details' => $monthlyDetails,
        ];
    }

    /**
     * Generate date-range monthly report with carry-forward support.
     */
    protected function generateDateRangeReport(int $memberId, string $memberName, ?string $fromDate = null, ?string $toDate = null): array
    {
        $nowEnd = now()->endOfMonth();

        if (empty($fromDate)) {
            $firstRecord = MemberMeasurement::where('member_id', $memberId)
                ->orderBy('measurement_date', 'asc')
                ->first();
            $start = $firstRecord ? Carbon::parse($firstRecord->measurement_date)->startOfMonth() : now()->subMonths(5)->startOfMonth();
        } else {
            $start = Carbon::parse($fromDate)->startOfMonth();
        }

        $end = empty($toDate) ? $nowEnd : Carbon::parse($toDate)->endOfMonth();

        // 1. Build monthly timeline array
        $period = CarbonPeriod::create($start, '1 month', $end);
        $monthsKeys = [];
        $monthsLabels = [];

        foreach ($period as $dt) {
            $monthsKeys[] = $dt->format('Y-m');
            $monthsLabels[] = $this->formatArabicMonthName($dt);
        }

        // 2. Fetch baseline record (latest record BEFORE $fromDate)
        $lastKnownRecord = MemberMeasurement::where('member_id', $memberId)
            ->where('measurement_date', '<', $start->toDateString())
            ->orderBy('measurement_date', 'desc')
            ->first();

        // 3. Fetch all measurements within the date range
        $allMeasurements = MemberMeasurement::where('member_id', $memberId)
            ->where('measurement_date', '>=', $start->toDateString())
            ->where('measurement_date', '<=', $end->toDateString())
            ->orderBy('measurement_date', 'asc')
            ->get();

        // Group measurements by YYYY-MM
        $measurementsByMonth = [];
        foreach ($allMeasurements as $measurement) {
            $mKey = Carbon::parse($measurement->measurement_date)->format('Y-m');
            $measurementsByMonth[$mKey] = $measurement;
        }

        $metricsDef = $this->getMetricsDefinition();
        $monthlyDetails = [];

        // Track time-series values per metric
        $metricValuesTimeSeries = [];
        foreach ($metricsDef as $fieldKey => $def) {
            $metricValuesTimeSeries[$fieldKey] = [];
        }

        // 4. Iterate over each month and calculate values (with Carry-Forward)
        foreach ($monthsKeys as $index => $mKey) {
            $monthLabel = $monthsLabels[$index];

            $hasActualRecord = false;
            $recordDate = null;
            $carriedFromDate = null;

            if (isset($measurementsByMonth[$mKey])) {
                $lastKnownRecord = $measurementsByMonth[$mKey];
                $hasActualRecord = true;
                $recordDate = Carbon::parse($lastKnownRecord->measurement_date)->toDateString();
            } elseif ($lastKnownRecord) {
                $hasActualRecord = false;
                $carriedFromDate = Carbon::parse($lastKnownRecord->measurement_date)->toDateString();
            }

            // Extract values snapshot
            $monthMeasurementsSnapshot = [];
            foreach ($metricsDef as $fieldKey => $def) {
                $val = $lastKnownRecord ? $lastKnownRecord->{$fieldKey} : null;

                if ($def['is_numeric'] && $val !== null) {
                    $val = (float) $val;
                }

                $monthMeasurementsSnapshot[$fieldKey] = $val;
                $metricValuesTimeSeries[$fieldKey][] = $val;
            }

            $monthlyDetails[] = [
                'month' => $mKey,
                'month_label' => $monthLabel,
                'has_actual_record' => $hasActualRecord,
                'record_date' => $recordDate,
                'carried_from_date' => $carriedFromDate,
                'measurements' => $monthMeasurementsSnapshot,
            ];
        }

        // 5. Organize metrics into categories and compute stats
        $metricsByCategory = $this->buildMetricsByCategory($metricsDef, $metricValuesTimeSeries, count($monthsKeys));

        return [
            'member' => [
                'id' => (int) $memberId,
                'name' => $memberName,
            ],
            'filter' => [
                'mode' => 'date_range',
                'from_date' => $start->toDateString(),
                'to_date' => $end->toDateString(),
                'records_count' => count($allMeasurements),
            ],
            'months' => $monthsKeys,
            'months_labels' => $monthsLabels,
            'metrics_by_category' => $metricsByCategory,
            'monthly_details' => $monthlyDetails,
        ];
    }

    /**
     * Group time-series metrics into categories with calculations.
     */
    protected function buildMetricsByCategory(array $metricsDef, array $metricValuesTimeSeries, int $recordsCount = 2): array
    {
        $metricsByCategory = [];

        foreach ($metricsDef as $fieldKey => $def) {
            $catKey = $def['category'];
            $catLabel = $def['category_label'];

            if (!isset($metricsByCategory[$catKey])) {
                $metricsByCategory[$catKey] = [
                    'category_label' => $catLabel,
                    'items' => [],
                ];
            }

            $series = $metricValuesTimeSeries[$fieldKey] ?? [];
            $initialValue = count($series) > 0 ? $series[0] : null;
            $finalValue = count($series) > 0 ? end($series) : null;
            $change = 0;
            $percentageChange = 0;
            $trend = 'stable';

            if ($recordsCount === 0 || count($series) === 0 || ($initialValue === null && $finalValue === null)) {
                $trend = 'no_data';
            } elseif ($recordsCount === 1 || count($series) === 1) {
                $trend = 'baseline'; // القياس المرجعي الأول
            } elseif ($def['is_numeric'] && $initialValue !== null && $finalValue !== null) {
                $change = round($finalValue - $initialValue, 2);
                if ($initialValue != 0) {
                    $percentageChange = round(($change / $initialValue) * 100, 2);
                }
                if ($change > 0) {
                    $trend = 'increased';
                } elseif ($change < 0) {
                    $trend = 'decreased';
                } else {
                    $trend = 'stable';
                }
            }

            $metricsByCategory[$catKey]['items'][$fieldKey] = [
                'label' => $def['label'],
                'unit' => $def['unit'],
                'values' => $series,
                'initial_value' => $initialValue,
                'final_value' => $finalValue,
                'change' => $change,
                'percentage_change' => $percentageChange,
                'trend' => $trend,
            ];
        }

        return $metricsByCategory;
    }
}
