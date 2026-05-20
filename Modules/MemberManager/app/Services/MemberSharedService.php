<?php

namespace Modules\MemberManager\Services;

use Modules\Core\Contracts\MemberSharedServiceInterface;
use Modules\Core\DTOs\MemberDTO;
use Modules\Core\DTOs\PersonDTO;
use Modules\MemberManager\Repositories\MemberRepositoryInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;

class MemberSharedService implements MemberSharedServiceInterface
{
    protected $repository;
    protected $personSharedService;

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

    public function getMemberByBarcode(string $barcode): ?MemberDTO
    {
        $member = $this->repository->findByBarcode($barcode);
        if (!$member) {
            return null;
        }

        return $this->mapToDTO($member);
    }

    protected function mapToDTO($member): MemberDTO
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
            barcode: $member->barcode_qr_code,
            status: $member->membership_status,
            isActive: (bool)$member->isActive,
            person: $personDTO
        );
    }
}
