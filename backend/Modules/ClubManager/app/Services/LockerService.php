<?php

namespace Modules\ClubManager\Services;

use Modules\ClubManager\Repositories\LockerRepositoryInterface;
use Modules\ClubManager\Domain\Rules\LockerUniquenessRule;
use Modules\ClubManager\Models\BranchSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;
use Carbon\Carbon;

class LockerService
{
    protected $repository;
    protected $uniquenessRule;

    public function __construct(
        LockerRepositoryInterface $repository,
        LockerUniquenessRule $uniquenessRule
    ) {
        $this->repository = $repository;
        $this->uniquenessRule = $uniquenessRule;
    }

    public function getAllLockers(array $filters = [])
    {
        // Fetch active reservation details along with holder name from people table
        $columns = [
            'lockers.id',
            'lockers.locker_number',
            'lockers.status',
            'lockers.branch_id',
            'locker_reservations.id as active_reservation_id',
            'locker_reservations.member_id as holder_member_id',
            'locker_reservations.staff_id as holder_staff_id',
            'locker_reservations.start_date',
            'locker_reservations.end_date',
            'locker_reservations.price',
            DB::raw('COALESCE(m_person.full_name, s_person.full_name, direct_person.full_name) as holder_name'),
            DB::raw('COALESCE(m_person.id, s_person.id, direct_person.id) as holder_person_id'),
            DB::raw('COALESCE(staff.id, staff_by_person.id, locker_reservations.staff_id) as resolved_staff_id'),
        ];
        if (Schema::hasColumn('lockers', 'key_number')) {
            $columns[] = 'lockers.key_number';
        }

        $query = DB::table('lockers')
            ->leftJoin('locker_reservations', function($join) {
                $join->on('lockers.id', '=', 'locker_reservations.locker_id')
                     ->where('locker_reservations.status', '=', 'active');
            })
            ->leftJoin('members', 'locker_reservations.member_id', '=', 'members.id')
            ->leftJoin('people as m_person', 'members.person_id', '=', 'm_person.id')
            ->leftJoin('staff', 'locker_reservations.staff_id', '=', 'staff.id')
            ->leftJoin('staff as staff_by_person', 'locker_reservations.staff_id', '=', 'staff_by_person.person_id')
            ->leftJoin('people as s_person', function($join) {
                $join->on('staff.person_id', '=', 's_person.id')
                     ->orOn('staff_by_person.person_id', '=', 's_person.id');
            })
            ->leftJoin('people as direct_person', 'locker_reservations.staff_id', '=', 'direct_person.id')
            ->select($columns)
            ->orderBy('lockers.locker_number');

        if (!empty($filters['branch_id'])) {
            $query->where('lockers.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'available') {
                $query->where('lockers.status', 'available');
            } elseif ($filters['status'] === 'occupied') {
                $query->where('lockers.status', '!=', 'available');
            }
        }

        $lockers = $query->get();

        // Batch fetch person_contacts for all holder person IDs
        $personIds = $lockers->pluck('holder_person_id')->filter()->unique()->values()->all();

        $contactsByPerson = [];
        if (!empty($personIds)) {
            $contacts = DB::table('person_contacts')
                ->whereIn('person_id', $personIds)
                ->whereNull('deleted_at')
                ->select('id', 'person_id', 'name', 'country_code', 'phone_number', 'relation')
                ->get();

            foreach ($contacts as $contact) {
                $contactsByPerson[$contact->person_id][] = [
                    'id'           => $contact->id,
                    'name'         => $contact->name,
                    'country_code' => $contact->country_code,
                    'phone_number' => $contact->phone_number,
                    'relation'     => $contact->relation,
                ];
            }
        }

        foreach ($lockers as $locker) {
            $locker->person_contacts = $contactsByPerson[$locker->holder_person_id] ?? [];
        }

        return $lockers;
    }

    public function createLocker(array $data)
    {
        $this->uniquenessRule->validate(
            $data['branch_id'],
            $data['locker_number'] ?? null,
            $data['key_number'] ?? null
        );
        return $this->repository->create($data);
    }

    public function getLockerById($id)
    {
        return $this->repository->find($id);
    }

    public function updateLocker($id, array $data)
    {
        $locker = $this->repository->find($id);
        $branchId = $data['branch_id'] ?? $locker->branch_id;
        $lockerNumber = $data['locker_number'] ?? $locker->locker_number;
        $keyNumber = array_key_exists('key_number', $data) ? $data['key_number'] : $locker->key_number;

        $this->uniquenessRule->validate($branchId, $lockerNumber, $keyNumber, $id);

        return $this->repository->update($id, $data);
    }

    /**
     * Delete a locker.
     *
     * @throws \Modules\Core\Exceptions\CannotDeleteException
     */
    public function deleteLocker($id)
    {
        $locker = \Modules\ClubManager\Models\Locker::findOrFail($id);
        
        $activeReservationsCount = \Modules\SubscriptionManager\Models\LockerReservation::where('locker_id', $id)
            ->where('status', 'active')
            ->count();

        if ($activeReservationsCount > 0 || $locker->status !== 'available') {
            throw new \Modules\Core\Exceptions\CannotDeleteException(
                "لا يمكن حذف الخزانة لأنها مستأجرة أو مسندة حالياً (يوجد حجز نشط). يُرجى إلغاء/إنهاء الحجز أولاً.",
                ['active_reservations_count' => $activeReservationsCount]
            );
        }

        \Modules\SubscriptionManager\Models\LockerReservation::where('locker_id', $id)->delete();

        return $this->repository->delete($id);
    }

    // --- New Unified API Methods ---

    public function reserveLocker(int $lockerId, array $data)
    {
        return DB::transaction(function () use ($lockerId, $data) {
            $locker = \Modules\ClubManager\Models\Locker::where('id', $lockerId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locker->status !== 'available') {
                throw new Exception(__('Locker is already occupied.'));
            }

            $reservationType = $data['reservation_type']; // 'rental' or 'assign'
            
            $price = 0;
            $paidAmount = 0;
            $startDate = null;
            $endDate = null;
            $invoiceId = null;

            if ($reservationType === 'rental') {
                // Must be member
                if (($data['holder_type'] ?? '') !== 'member' || empty($data['holder_id'])) {
                    throw new Exception(__('Rentals are only available for members.'));
                }

                $price = $data['price'];
                $paidAmount = isset($data['paid_amount']) ? floatval($data['paid_amount']) : floatval($price);
                $startDate = $data['start_date'];
                $endDate = $data['end_date'];

                $invoiceStatus = 'unpaid';
                if ($paidAmount >= $price && $price > 0) {
                    $invoiceStatus = 'paid';
                } elseif ($paidAmount > 0) {
                    $invoiceStatus = 'partially_paid';
                }

                // Create Invoice
                $invoice = \Modules\SubscriptionManager\Models\Invoice::create([
                    'member_id' => $data['holder_id'],
                    'branch_id' => $locker->branch_id,
                    'total' => $price,
                    'status' => $invoiceStatus,
                ]);
                $invoiceId = $invoice->id;
            } else {
                // For coach, startDate and endDate strictly remain null
                if (($data['holder_type'] ?? '') !== 'coach') {
                    $startDate = $data['start_date'] ?? now();
                    $endDate = $data['end_date'] ?? null;
                }
            }

            $staffId = null;
            if (in_array($data['holder_type'] ?? '', ['staff', 'coach']) && !empty($data['holder_id'])) {
                $staffRecord = DB::table('staff')->where('id', $data['holder_id'])->first();
                if (!$staffRecord) {
                    $staffRecord = DB::table('staff')->where('person_id', $data['holder_id'])->first();
                }
                $staffId = $staffRecord?->id ?? $data['holder_id'];
            }

            // Create reservation
            $reservationId = DB::table('locker_reservations')->insertGetId([
                'locker_id' => $lockerId,
                'member_id' => ($data['holder_type'] ?? '') === 'member' ? $data['holder_id'] : null,
                'staff_id' => $staffId,
                'invoice_id' => $invoiceId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $price,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link reservation to invoice & record payment
            if ($invoiceId) {
                DB::table('invoices')->where('id', $invoiceId)->update([
                    'locker_reservation_id' => $reservationId,
                ]);

                if ($paidAmount > 0) {
                    $safeId = $data['safe_id'] ?? null;
                    if (!$safeId) {
                        $safeId = DB::table('acc_branch_settings')
                            ->where('branch_id', $locker->branch_id)
                            ->value('default_safe_id');
                        if (!$safeId) {
                            $safeId = DB::table('acc_safes')
                                ->where('branch_id', $locker->branch_id)
                                ->value('id');
                        }
                    }

                    \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoiceId,
                        'safe_id' => $safeId,
                        'amount' => $paidAmount,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'status' => 'completed',
                    ]);
                }
            }

            // Update locker status
            $statusMap = [
                'member' => 'with_member',
                'staff' => 'with_staff',
                'coach' => 'with_coach',
            ];
            $newStatus = $statusMap[$data['holder_type'] ?? 'member'] ?? 'with_member';

            $this->repository->update($lockerId, ['status' => $newStatus]);

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($locker->branch_id);
            }

            return DB::table('locker_reservations')->where('id', $reservationId)->first();
        });
    }

    public function releaseLocker(int $lockerId)
    {
        return DB::transaction(function () use ($lockerId) {
            $locker = \Modules\ClubManager\Models\Locker::where('id', $lockerId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locker->status === 'available') {
                throw new Exception(__('Locker is already available.'));
            }

            // End active reservation
            DB::table('locker_reservations')
                ->where('locker_id', $lockerId)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'end_date' => DB::raw('COALESCE(end_date, NOW())'),
                    'updated_at' => now(),
                ]);

            $this->repository->update($lockerId, ['status' => 'available']);

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($locker->branch_id);
            }

            return true;
        });
    }

    public function transferReservationHolder(int $reservationId, array $data)
    {
        return DB::transaction(function () use ($reservationId, $data) {
            $reservation = DB::table('locker_reservations')
                ->where('id', $reservationId)
                ->lockForUpdate()
                ->first();
            
            if (!$reservation || $reservation->status !== 'active') {
                throw new Exception(__('Active reservation not found.'));
            }

            $holderType = $data['holder_type'];
            $staffId = null;
            if (in_array($holderType, ['staff', 'coach']) && !empty($data['holder_id'])) {
                $staffRecord = DB::table('staff')->where('id', $data['holder_id'])->first();
                if (!$staffRecord) {
                    $staffRecord = DB::table('staff')->where('person_id', $data['holder_id'])->first();
                }
                $staffId = $staffRecord?->id ?? $data['holder_id'];
            }
            
            DB::table('locker_reservations')->where('id', $reservationId)->update([
                'member_id' => $holderType === 'member' ? $data['holder_id'] : null,
                'staff_id' => $staffId,
                'updated_at' => now(),
            ]);

            // Update locker status
            $statusMap = [
                'member' => 'with_member',
                'staff' => 'with_staff',
                'coach' => 'with_coach',
            ];
            $newStatus = $statusMap[$holderType] ?? 'with_member';

            $this->repository->update($reservation->locker_id, ['status' => $newStatus]);

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $locker = \Modules\ClubManager\Models\Locker::find($reservation->locker_id);
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($locker?->branch_id);
            }

            return DB::table('locker_reservations')->where('id', $reservationId)->first();
        });
    }

    public function getLockersByHolder($holderType, $holderId)
    {
        return DB::table('lockers')
            ->join('locker_reservations', 'lockers.id', '=', 'locker_reservations.locker_id')
            ->where('locker_reservations.status', '=', 'active')
            ->where(function($query) use ($holderType, $holderId) {
                if ($holderType === 'member') {
                    $query->where('locker_reservations.member_id', $holderId);
                } elseif ($holderType === 'staff' || $holderType === 'coach') {
                    $query->where('locker_reservations.staff_id', $holderId);
                }
            })
            ->select(
                'lockers.id',
                'lockers.locker_number',
                'lockers.key_number',
                'lockers.status',
                'lockers.branch_id',
                'locker_reservations.id as active_reservation_id',
                'locker_reservations.start_date',
                'locker_reservations.end_date',
                'locker_reservations.price',
                'locker_reservations.member_id',
                'locker_reservations.staff_id'
            )
            ->get();
    }

    /**
     * Get aggregated counts for lockers.
     *
     * @param int|null $branchId
     * @return array
     */
    public function getLockersSummary(?int $branchId = null): array
    {
        $baseQuery = DB::table('lockers');
        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        }

        $rentedQuery = DB::table('locker_reservations as lr')
            ->join('lockers as l', 'l.id', '=', 'lr.locker_id')
            ->where('lr.status', 'active')
            ->where('lr.price', '>', 0);
        if ($branchId) {
            $rentedQuery->where('l.branch_id', $branchId);
        }

        return [
            'available_lockers_count'   => (clone $baseQuery)->where('status', 'available')->count(),
            'unavailable_lockers_count' => (clone $baseQuery)->where('status', '!=', 'available')->count(),
            'assigned_to_member_count'  => (clone $baseQuery)->where('status', 'with_member')->count(),
            'assigned_to_staff_count'   => (clone $baseQuery)->where('status', 'with_staff')->count(),
            'assigned_to_coach_count'   => (clone $baseQuery)->where('status', 'with_coach')->count(),
            'rented_lockers_count'      => $rentedQuery->distinct('lr.locker_id')->count('lr.locker_id'),
        ];
    }
}
