<?php

namespace Modules\MemberManager\Services;

use Modules\Core\Contracts\MemberSharedServiceInterface;
use Modules\Core\DTOs\MemberDTO;
use Modules\Core\DTOs\PersonDTO;
use Modules\MemberManager\Repositories\MemberRepositoryInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Authentication\Services\PersonQrCodeService;
use Modules\Authentication\Models\PersonQrCode;

class MemberSharedService implements MemberSharedServiceInterface
{
    protected MemberRepositoryInterface $repository;
    protected PersonSharedServiceInterface $personSharedService;

    public function __construct(
        MemberRepositoryInterface $repository,
        PersonSharedServiceInterface $personSharedService
    ) {
        $this->repository = $repository;
        $this->personSharedService = $personSharedService;
    }

    public function getMemberById(int $id): ?MemberDTO
    {
        try {
            $member = $this->repository->find($id);
            if (!$member) {
                return null;
            }
            return $this->mapToDTO($member);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMembersByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        try {
            $members = \Modules\MemberManager\Models\Member::whereIn('id', $ids)->get();
            $dtos = [];
            foreach ($members as $member) {
                $dtos[] = $this->mapToDTO($member);
            }
            return $dtos;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getMemberByBarcode(string $barcode): ?MemberDTO
    {
        // Look up which person owns this QR code
        $qrRecord = PersonQrCode::where('code', $barcode)->first();
        if (!$qrRecord) {
            return null;
        }

        $member = $this->repository->findByPersonId($qrRecord->person_id);
        if (!$member) {
            return null;
        }

        return $this->mapToDTO($member);
    }

    protected function mapToDTO(\Modules\MemberManager\Models\Member $member): MemberDTO
    {
        $personDTO = null;
        if ($member->person_id) {
            $personDTO = $this->personSharedService->getPersonById($member->person_id);
        }

        return new MemberDTO(
            id: $member->id,
            personId: $member->person_id,
            branchId: $member->branch_id,
            memberNumber: $member->member_number,
            barcode: null, // QR codes are now in person_qr_codes table
            status: $member->membership_status,
            isActive: (bool)$member->isActive,
            person: $personDTO
        );
    }
}
