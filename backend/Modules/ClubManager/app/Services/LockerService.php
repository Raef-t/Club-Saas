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
            DB::raw('COALESCE(m_person.full_name, s_person.full_name) as holder_name'),
            DB::raw('COALESCE(m_person.id, s_person.id) as holder_person_id'),
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
            ->leftJoin('people as s_person', 'staff.person_id', '=', 's_person.id')
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
            $startDate = now();
            $endDate = null;
            $invoiceId = null;

            if ($reservationType === 'rental') {
                // Must be member
                if (($data['holder_type'] ?? '') !== 'member' || empty($data['holder_id'])) {
                    throw new Exception(__('Rentals are only available for members.'));
                }

                $price = $data['price'];
                $startDate = $data['start_date'];
                $endDate = $data['end_date'];

                // Create Invoice
                $invoice = \Modules\SubscriptionManager\Models\Invoice::create([
                    'member_id' => $data['holder_id'],
                    'branch_id' => $locker->branch_id,
                    'total' => $price,
                    'status' => 'unpaid',
                ]);
                $invoiceId = $invoice->id;
            }

            // Create reservation
            $reservationId = DB::table('locker_reservations')->insertGetId([
                'locker_id' => $lockerId,
                'member_id' => ($data['holder_type'] ?? '') === 'member' ? $data['holder_id'] : null,
                'staff_id' => ($data['holder_type'] ?? '') === 'staff' ? $data['holder_id'] : null,
                'invoice_id' => $invoiceId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $price,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update locker status
            $statusMap = [
                'member' => 'with_member',
                'staff' => 'with_staff',
                'guest' => 'with_guest',
            ];
            $newStatus = $statusMap[$data['holder_type'] ?? 'member'] ?? 'with_member';

            $this->repository->update($lockerId, ['status' => $newStatus]);

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
            
            DB::table('locker_reservations')->where('id', $reservationId)->update([
                'member_id' => $holderType === 'member' ? $data['holder_id'] : null,
                'staff_id' => $holderType === 'staff' ? $data['holder_id'] : null,
                'updated_at' => now(),
            ]);

            // Update locker status
            $statusMap = [
                'member' => 'with_member',
                'staff' => 'with_staff',
                'guest' => 'with_guest',
            ];
            $newStatus = $statusMap[$holderType] ?? 'with_member';

            $this->repository->update($reservation->locker_id, ['status' => $newStatus]);

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
                } elseif ($holderType === 'staff') {
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
            ->where('lr.status', 'active');
        if ($branchId) {
            $rentedQuery->where('l.branch_id', $branchId);
        }

        return [
            'available_lockers_count'   => (clone $baseQuery)->where('status', 'available')->count(),
            'unavailable_lockers_count' => (clone $baseQuery)->where('status', '!=', 'available')->count(),
            'assigned_to_member_count'  => (clone $baseQuery)->where('status', 'with_member')->count(),
            'assigned_to_coach_count'   => (clone $baseQuery)->where('status', 'with_staff')->count(),
            'rented_lockers_count'      => $rentedQuery->distinct('lr.locker_id')->count('lr.locker_id'),
        ];
    }
}
