<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccSalaryPaymentResource",
    title: "مورد تفاصيل صرف راتب الكادر",
    description: "يمثل كائن إرجاع تفاصيل سند صرف راتب لموظف أو مدرب بالنادي الرياضي.",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(
            property: "staff",
            type: "object",
            nullable: true,
            properties: [
                new OA\Property(property: "id", type: "integer", example: 5),
                new OA\Property(property: "first_name", type: "string", example: "أحمد"),
                new OA\Property(property: "last_name", type: "string", example: "المحمد"),
                new OA\Property(property: "role", type: "string", example: "coach")
            ]
        ),
        new OA\Property(
            property: "safe",
            type: "object",
            nullable: true,
            properties: [
                new OA\Property(property: "id", type: "integer", example: 2),
                new OA\Property(property: "name", type: "string", example: "صندوق الاستقبال الرئيسي")
            ]
        ),
        new OA\Property(
            property: "period",
            type: "object",
            nullable: true,
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "شهر حزيران 2026")
            ]
        ),
        new OA\Property(property: "amount", type: "number", format: "float", example: 500.00),
        new OA\Property(property: "currency", type: "string", example: "USD"),
        new OA\Property(property: "payment_type", type: "string", enum: ["salary", "advance", "bonus"], example: "salary"),
        new OA\Property(property: "date", type: "string", format: "date", example: "2026-07-01"),
        new OA\Property(property: "notes", type: "string", nullable: true, example: "راتب شهر حزيران"),
        new OA\Property(property: "journal_id", type: "integer", nullable: true, example: 12),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-07-01T10:00:00Z")
    ]
)]
class AccSalaryPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        $person = $this->relationLoaded('staff') && $this->staff ? $this->staff->person : null;
        $fullName = $person ? ($person->full_name ?? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''))) : null;

        return [
            'id'           => $this->id,
            'staff'        => $this->relationLoaded('staff') && $this->staff ? [
                'id'         => $this->staff->id,
                'first_name' => $fullName,
                'last_name'  => '',
                'role'       => $this->staff->role,
                'person'     => $person ? [
                    'id'        => $person->id,
                    'full_name' => $fullName,
                ] : null,
            ] : null,
            'staff_name'   => $fullName,
            'safe'         => $this->relationLoaded('safe') && $this->safe ? [
                'id'   => $this->safe->id,
                'name' => $this->safe->name,
            ] : null,
            'period'       => $this->relationLoaded('period') && $this->period ? [
                'id'   => $this->period->id,
                'name' => $this->period->name,
            ] : null,
            'amount'       => $this->amount,
            'currency'     => $this->currency,
            'payment_type' => $this->payment_type ?? 'salary',
            'date'         => $this->date ? $this->date->toDateString() : null,
            'payment_date' => $this->date ? $this->date->toDateString() : null,
            'payslip_id'   => $this->payslip_id,
            'payslip'     => $this->relationLoaded('payslip') && $this->payslip ? [
                'id'             => $this->payslip->id,
                'payroll_run_id' => $this->payslip->payroll_run_id,
                'net_pay'        => $this->payslip->net_pay,
            ] : null,
            'notes'       => $this->notes,
            'journal_id'  => $this->journal_id,
            'created_at'  => $this->created_at,
        ];
    }
}
