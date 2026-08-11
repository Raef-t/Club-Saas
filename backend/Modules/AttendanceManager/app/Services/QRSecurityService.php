<?php

namespace Modules\AttendanceManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\Authentication\Services\PersonQrCodeService;

class QRSecurityService
{
    public function __construct(
        protected PersonQrCodeService $qrCodeService
    ) {}

    /**
     * Validate a static QR code sent from the frontend cache.
     * Checks both code existence AND that it matches today's day of week.
     * Returns the person_id if valid, or throws an exception.
     *
     * @param  string  $code  One of the 7 static codes (1 per day) belonging to the person
     * @return int  person_id
     * @throws \Exception
     */
    public function validateCode(string $code): int
    {
        // Delegates to PersonQrCodeService which handles both existence + day validation
        return $this->qrCodeService->resolvePersonByCode($code);
    }

    /**
     * Resolve member_id from a QR code.
     * Used by check-in/check-out endpoints that still operate on member_id.
     *
     * @param  string  $code
     * @return int  member_id
     * @throws \Exception
     */
    public function validateCodeForMember(string $code): int
    {
        $personId = $this->validateCode($code);

        $member = DB::table('members')
            ->where('person_id', $personId)
            ->first();

        if (!$member) {
            throw new \Exception('No active member found for this QR code.');
        }

        return (int) $member->id;
    }

    /**
     * Resolve staff_id from a QR code.
     *
     * @param  string  $code
     * @return int  staff_id
     * @throws \Exception
     */
    public function validateCodeForStaff(string $code): int
    {
        $personId = $this->validateCode($code);

        $staff = DB::table('staff')
            ->where('person_id', $personId)
            ->first();

        if (!$staff) {
            throw new \Exception('No active staff found for this QR code.');
        }

        return (int) $staff->id;
    }

    /**
     * @deprecated Use validateCode() instead.
     * Kept for backward compatibility during migration.
     */
    public function validateToken(string $token): int
    {
        return $this->validateCodeForMember($token);
    }
}
