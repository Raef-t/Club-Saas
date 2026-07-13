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
        $assignedAt = null;

        // 1. إذا كانت البيانات قادمة من الاستعلام المباشر (استرجاع جميع الخزائن)
        if ($this->resource instanceof \stdClass) {
            if ($this->status === 'with_member') {
                $holderType = 'member';
                $holderId = $this->holder_member_id ?? null;
            } elseif ($this->status === 'with_staff') {
                $holderType = 'staff';
                $holderId = $this->holder_staff_id ?? null;
            } elseif ($this->status === 'with_guest') {
                $holderType = 'guest';
            }
            $assignedAt = $this->start_date ?? null;
        } 
        // 2. إذا كانت البيانات قادمة من النموذج المباشر (إضافة / تعديل / عرض مفرد)
        elseif ($this->resource instanceof Locker) {
            // جلب الحجز النشط في حال وجوده لمعرفة المالك
            $activeRes = DB::table('locker_reservations')
                ->where('locker_id', $this->id)
                ->where('status', 'active')
                ->first();

            if ($activeRes) {
                if ($this->status === 'with_member') {
                    $holderType = 'member';
                    $holderId = $activeRes->member_id;
                } elseif ($this->status === 'with_staff') {
                    $holderType = 'staff';
                    $holderId = $activeRes->staff_id;
                } elseif ($this->status === 'with_guest') {
                    $holderType = 'guest';
                }
                $assignedAt = $activeRes->start_date ?? null;
            }
        }

        return [
            'id'            => $this->id,
            'branch_id'     => $this->branch_id,
            'locker_number' => $this->locker_number,

            // ── Current state ──────────────────────────────────────────────
            'status'        => $this->status,

            // ── Holder (polymorphic) ───────────────────────────────────────
            'holder_id'     => $holderId,
            'holder_type'   => $holderType,
            'holder_name'   => $this->holder_name ?? null,
            'assigned_at'   => $assignedAt ? Carbon::parse($assignedAt)->toIso8601String() : null,

            // ── Relations ─────────────────────────────────────────────────
            'branch'        => $this->when($this->resource instanceof \Illuminate\Database\Eloquent\Model, function () {
                return new BranchResource($this->whenLoaded('branch'));
            }),

            'created_at'    => isset($this->created_at) 
                                ? (is_string($this->created_at) ? $this->created_at : $this->created_at->format('Y-m-d H:i:s')) 
                                : null,
        ];
    }
}
