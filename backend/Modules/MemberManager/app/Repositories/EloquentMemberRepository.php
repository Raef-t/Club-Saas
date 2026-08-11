<?php

namespace Modules\MemberManager\Repositories;

use Modules\MemberManager\Models\Member;

class EloquentMemberRepository implements MemberRepositoryInterface
{
    public function all()
    {
        return Member::all();
    }

    public function find($id)
    {
        return Member::with(['healthProfile', 'measurements'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Member::create($data);
    }

    public function update($id, array $data)
    {
        $member = $this->find($id);
        $member->update($data);
        return $member;
    }

    public function delete($id)
    {
        $member = $this->find($id);
        return $member->delete();
    }

    public function findByMemberNumber($number)
    {
        return Member::where('member_number', $number)->first();
    }

    public function findByPersonId(int $personId): ?Member
    {
        return Member::where('person_id', $personId)->first();
    }

    /**
     * @deprecated QR codes are now in person_qr_codes table.
     * Use PersonQrCodeService + findByPersonId instead.
     */
    public function findByBarcode($barcode)
    {
        return null; // Legacy - no longer supported
    }

    public function getTrashed(array $filters = [])
    {
        $query = Member::onlyTrashed()->with(['person.contacts', 'branch', 'healthProfile']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->latest()->get();
    }

    public function restore(int $id)
    {
        $member = Member::onlyTrashed()->findOrFail($id);
        $member->restore();
        return $member;
    }
}
