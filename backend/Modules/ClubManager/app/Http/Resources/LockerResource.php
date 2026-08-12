<?php

namespace Modules\ClubManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\ClubManager\Models\Locker;
use Carbon\Carbon;

class LockerResource extends JsonResource
{
    public function toArray($request): array
    {
        $holderType = null;
        $holderId = null;
        $holderName = null;
        $assignedAt = null;
        $personContacts = [];

        // 1. إذا كانت البيانات قادمة من الاستعلام المباشر (استرجاع جميع الخزائن)
        if ($this->resource instanceof \stdClass) {
            if ($this->status === 'with_member') {
                $holderType = 'member';
                $holderId = $this->holder_member_id ?? null;
            } elseif ($this->status === 'with_staff') {
                $holderType = 'staff';
                $holderId = $this->resolved_staff_id ?? $this->holder_staff_id ?? null;
            } elseif ($this->status === 'with_coach') {
                $holderType = 'coach';
                $holderId = $this->resolved_staff_id ?? $this->holder_staff_id ?? null;
            }
            $holderName = $this->holder_name ?? null;
            $assignedAt = $this->start_date ?? null;
            $personContacts = $this->person_contacts ?? [];
        } 
        // 2. إذا كانت البيانات قادمة من النموذج المباشر (إضافة / تعديل / عرض مفرد)
        elseif ($this->resource instanceof Locker) {
            // جلب الحجز النشط في حال وجوده لمعرفة المالك
            $activeRes = DB::table('locker_reservations')
                ->where('locker_id', $this->id)
                ->where('status', 'active')
                ->first();

            if ($activeRes) {
                $personId = null;
                if ($this->status === 'with_member' && $activeRes->member_id) {
                    $holderType = 'member';
                    $holderId = $activeRes->member_id;
                    $member = DB::table('members')->where('id', $holderId)->first();
                    $personId = $member?->person_id;
                } elseif (($this->status === 'with_staff' || $this->status === 'with_coach') && $activeRes->staff_id) {
                    $holderType = $this->status === 'with_coach' ? 'coach' : 'staff';
                    $staff = DB::table('staff')->where('id', $activeRes->staff_id)->first();
                    if (!$staff) {
                        $staff = DB::table('staff')->where('person_id', $activeRes->staff_id)->first();
                    }
                    $holderId = $staff?->id ?? $activeRes->staff_id;
                    $personId = $staff?->person_id ?? (DB::table('people')->where('id', $activeRes->staff_id)->exists() ? $activeRes->staff_id : null);
                }

                if ($personId) {
                    $person = DB::table('people')->where('id', $personId)->first();
                    $holderName = $person?->full_name;
                    $personContacts = DB::table('person_contacts')
                        ->where('person_id', $personId)
                        ->whereNull('deleted_at')
                        ->select('id', 'name', 'country_code', 'phone_number', 'relation')
                        ->get()
                        ->toArray();
                }

                $assignedAt = $activeRes->start_date ?? null;
            }
        }

        return [
            'id'              => $this->id,
            'branch_id'       => $this->branch_id,
            'locker_number'   => $this->locker_number,
            'key_number'      => $this->key_number ?? null,

            // ── Current state ──────────────────────────────────────────────
            'status'          => $this->status,

            // ── Holder (polymorphic) ───────────────────────────────────────
            'holder_id'       => $holderId,
            'holder_type'     => $holderType,
            'holder_name'     => $holderName,
            'assigned_at'     => $assignedAt ? Carbon::parse($assignedAt)->toIso8601String() : null,

            // ── Contacts ──────────────────────────────────────────────────
            'contact_person'  => $personContacts,

            // ── Relations ─────────────────────────────────────────────────
            'branch'          => $this->when($this->resource instanceof \Illuminate\Database\Eloquent\Model, function () {
                return new BranchResource($this->whenLoaded('branch'));
            }),

            'created_at'      => isset($this->created_at) 
                                ? (is_string($this->created_at) ? $this->created_at : $this->created_at->format('Y-m-d H:i:s')) 
                                : null,
        ];
    }
}
