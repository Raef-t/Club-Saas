<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $latestPayment = $this->relationLoaded('payments')
            ? $this->payments->sortByDesc('created_at')->first()
            : null;

        return [
            'invoice_id' => $this->id,
            'amount' => (float) $this->total,
            'formatted_amount' => '$' . number_format($this->total, 0),
            'status' => $this->status,
            'receipt_number' => $latestPayment?->receipt_number ?? null,
            'payment_method' => $latestPayment?->payment_method ?? null,
            'paid_at' => $latestPayment?->created_at?->toDateString() ?? $this->created_at?->toDateString(),
            'created_at' => $this->created_at?->toDateString(),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
