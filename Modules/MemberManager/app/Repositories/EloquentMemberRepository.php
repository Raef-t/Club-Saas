<?php

namespace Modules\MemberManager\Repositories;

use Modules\MemberManager\Models\Member;

class EloquentMemberRepository implements MemberRepositoryInterface
{
    public function all()
    {
        return Member::with(['person', 'branch'])->get();
    }

    public function find($id)
    {
        return Member::with(['person', 'branch', 'healthProfile', 'measurements'])->findOrFail($id);
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

    public function findByBarcode($barcode)
    {
        return Member::where('barcode_qr_code', $barcode)->first();
    }
}
