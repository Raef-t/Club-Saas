<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccJournalEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'journal_id' => $this->journal_id,
            'account_id' => $this->account_id,
            'debit_usd'  => $this->debit_usd,
            'credit_usd' => $this->credit_usd,
            'debit_syp'  => $this->debit_syp,
            'credit_syp' => $this->credit_syp,
            'memo'       => $this->memo,
            'account'    => $this->whenLoaded('account', fn() => new AccAccountResource($this->account)),
        ];
    }
}
