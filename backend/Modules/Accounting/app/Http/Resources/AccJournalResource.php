<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccJournalResource extends JsonResource
{
    public function toArray($request): array
    {
        $entries = $this->whenLoaded('entries');

        return [
            'id'               => $this->id,
            'number'           => $this->reference_number,
            'reference_number' => $this->reference_number,
            'type'             => $this->type,
            'date'             => $this->date?->format('Y-m-d'),
            'description'      => $this->description,
            'status'           => $this->status,
            'exchange_rate'    => $this->exchange_rate,
            'posted_at'        => $this->posted_at,
            'source_type'      => $this->source_type,
            'source_id'        => $this->source_id,
            'reversed_journal_id' => $this->reversed_journal_id,
            'is_reversal'         => $this->relationLoaded('reversesJournal') 
                ? $this->reversesJournal !== null 
                : \Modules\Accounting\Models\AccJournal::where('reversed_journal_id', $this->id)->exists(),
            'notes'            => $this->notes,
            'period'           => $this->whenLoaded('period',       fn() => new AccPeriodResource($this->period)),
            'safe'             => $this->whenLoaded('safe',         fn() => new AccSafeResource($this->safe)),
            'counterparty'     => $this->whenLoaded('counterparty', fn() => new AccCounterpartyResource($this->counterparty)),
            'entries'          => $this->whenLoaded('entries',      fn() => AccJournalEntryResource::collection($this->entries)),
            'total_debit_usd'  => $this->whenLoaded('entries', fn() => (float) $this->entries->sum('debit_usd'),  0),
            'total_credit_usd' => $this->whenLoaded('entries', fn() => (float) $this->entries->sum('credit_usd'), 0),
            'total_debit_syp'  => $this->whenLoaded('entries', fn() => (float) $this->entries->sum('debit_syp'),  0),
            'total_credit_syp' => $this->whenLoaded('entries', fn() => (float) $this->entries->sum('credit_syp'), 0),
            'created_at'       => $this->created_at,
        ];
    }
}
