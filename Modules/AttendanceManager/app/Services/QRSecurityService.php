<?php

namespace Modules\AttendanceManager\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\MemberManager\Models\Member;

class QRSecurityService
{
    /**
     * Generate a short-lived secure QR token.
     */
    public function generateToken(Member $member): string
    {
        $payload = [
            'member_id' => $member->id,
            'expires_at' => now()->addSeconds(30)->timestamp,
            'nonce' => uniqid(mt_rand(), true)
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Validate the QR token and prevent replay attacks.
     * Returns the member ID if valid, or throws an exception.
     */
    public function validateToken(string $token): int
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);

            if (!$payload || !isset($payload['member_id'], $payload['expires_at'], $payload['nonce'])) {
                throw new \Exception('Invalid token structure.');
            }

            if (now()->timestamp > $payload['expires_at']) {
                $this->logAttempt($payload['member_id'], $token, false, 'Token expired.');
                throw new \Exception('QR code expired.');
            }

            $signature = hash('sha256', $token);

            // Check for replay attack
            $exists = DB::table('qr_access_logs')->where('token_signature', $signature)->exists();
            if ($exists) {
                $this->logAttempt($payload['member_id'], $token, false, 'Replay attack detected.');
                throw new \Exception('QR code already used.');
            }

            // Log successful attempt
            $this->logAttempt($payload['member_id'], $token, true);

            return $payload['member_id'];

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            throw new \Exception('Invalid QR code signature.');
        }
    }

    protected function logAttempt($memberId, $token, $isSuccessful, $reason = null)
    {
        DB::table('qr_access_logs')->insert([
            'member_id' => $memberId ?? 0,
            'token_signature' => hash('sha256', $token),
            'used_at' => now(),
            'is_successful' => $isSuccessful,
            'failure_reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
