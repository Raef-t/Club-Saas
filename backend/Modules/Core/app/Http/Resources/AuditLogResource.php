<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->properties ?? [];
        $causer = $this->causer ?? $this->user;

        $causerName = null;
        if ($causer) {
            if (isset($causer->person)) {
                $firstName = $causer->person->first_name ?? '';
                $lastName  = $causer->person->last_name ?? '';
                $causerName = trim("{$firstName} {$lastName}");
                if (empty($causerName)) {
                    $causerName = $causer->person->full_name ?? null;
                }
            }
            if (empty($causerName)) {
                $causerName = $causer->username ?? $causer->name ?? $causer->email ?? ('User #' . $causer->id);
            }
        }

        return [
            'id'             => $this->id,
            'log_name'       => $this->log_name,
            'event'          => $this->event,
            'description'    => $this->description,
            'branch_id'      => $this->branch_id,
            'causer'         => $causer ? [
                'id'       => $causer->id,
                'name'     => $causerName,
                'username' => $causer->username ?? null,
            ] : null,
            'subject'        => [
                'type'      => $this->subject_type ? class_basename($this->subject_type) : null,
                'full_type' => $this->subject_type,
                'id'        => $this->subject_id,
            ],
            'changes'        => [
                'old'        => $properties['old'] ?? null,
                'attributes' => $properties['attributes'] ?? null,
            ],
            'created_at'     => $this->created_at?->toIso8601String(),
            'formatted_date' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
