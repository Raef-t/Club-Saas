<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        $latestPayment = $this->whenLoaded('payments', function () {
            return $this->payments->sortByDesc('created_at')->first();
        });

        return [
            'invoice_id' => $this->id,
            'amount' => (float) $this->total,
            'formatted_amount' => '$' . number_format($this->total, 0),
            'status' => $this->status,
            'payment_method' => $latestPayment?->payment_method ?? null,
            'paid_at' => $latestPayment?->created_at?->toDateString() ?? $this->created_at?->toDateString(),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
