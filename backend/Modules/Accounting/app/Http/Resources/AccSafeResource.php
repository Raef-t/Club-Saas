<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccSafeResource extends JsonResource
{
    public function toArray($request): array
    {
        $currentBalance = 0.0;
        if ($this->account_id) {
            $isUsd = $this->currency === 'USD';
            $safeId = $this->id;
            $branchId = $this->branch_id;
            $entries = \Modules\Accounting\Models\AccJournalEntry::where('account_id', $this->account_id)
                ->whereHas('journal', function ($q) use ($safeId, $branchId) {
                    $q->where('status', 'posted')
                      ->where(function ($sq) use ($safeId, $branchId) {
                          $sq->where('safe_id', $safeId);
                          if ($branchId) {
                              $sq->orWhere(function ($ssq) use ($branchId) {
                                  $ssq->whereNull('safe_id')->where('branch_id', $branchId);
                              });
                          }
                      });
                })
                ->selectRaw('SUM(debit_usd) as debit_usd, SUM(credit_usd) as credit_usd, SUM(debit_syp) as debit_syp, SUM(credit_syp) as credit_syp')
                ->first();
            if ($entries) {
                $currentBalance = $isUsd
                    ? (float)(($entries->debit_usd ?? 0) - ($entries->credit_usd ?? 0))
                    : (float)(($entries->debit_syp ?? 0) - ($entries->credit_syp ?? 0));
            }
        }

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'currency'        => $this->currency,
            'current_balance' => round($currentBalance, 2),
            'is_active'       => $this->is_active,
            'notes'           => $this->notes,
            'branch_id'       => $this->branch_id,
            'branch'          => $this->whenLoaded('branch', fn() => [
                'id'   => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'account'         => $this->whenLoaded('account', fn() => new AccAccountResource($this->account)),
            'created_at'      => $this->created_at,
        ];
    }
}
