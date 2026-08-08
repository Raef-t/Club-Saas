<?php

namespace Modules\Authentication\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Authentication\Models\PersonQrCode;

class PersonQrCodeService
{
    /**
     * Days of the week: 0=Sunday ... 6=Saturday (matches PHP's date('w'))
     */
    const DAYS = [0, 1, 2, 3, 4, 5, 6];

    /**
     * Generate one unique QR code per day of the week for a given person.
     * Safe to call multiple times — skips days that already have a code.
     *
     * @param  int  $personId
     * @return array  Keyed by day_of_week => code
     */
    public function generateForPerson(int $personId): array
    {
        $existing = PersonQrCode::where('person_id', $personId)
            ->pluck('code', 'day_of_week')
            ->toArray();

        // If all 7 days already have codes, return them as-is
        if (count($existing) >= 7) {
            return $existing;
        }

        foreach (self::DAYS as $day) {
            if (!isset($existing[$day])) {
                $code = $this->makeUniqueCode();
                PersonQrCode::create([
                    'person_id'   => $personId,
                    'code'        => $code,
                    'day_of_week' => $day,
                ]);
                $existing[$day] = $code;
            }
        }

        ksort($existing);
        return $existing;
    }

    /**
     * Return all 7 QR codes for a person, keyed by day_of_week.
     * Frontend stores this map in cache and uses today's code.
     *
     * @param  int  $personId
     * @return array  [0 => 'QR-...', 1 => 'QR-...', ...]
     */
    public function getCodesForPerson(int $personId): array
    {
        return PersonQrCode::where('person_id', $personId)
            ->pluck('code', 'day_of_week')
            ->toArray();
    }

    /**
     * Given a QR code string, validate that:
     *   1. The code exists in the database.
     *   2. The code's day_of_week matches TODAY.
     *
     * Returns the person_id on success, or throws an exception.
     *
     * @param  string  $code
     * @return int  person_id
     * @throws \Exception
     */
    public function resolvePersonByCode(string $code): int
    {
        $record = PersonQrCode::where('code', $code)->first();

        if (!$record) {
            throw new \Exception('Invalid QR code.');
        }

        $todayDayOfWeek = (int) Carbon::now()->format('w'); // 0=Sun, 6=Sat

        if ($record->day_of_week !== $todayDayOfWeek) {
            $dayNames = PersonQrCode::DAY_NAMES;
            $expected = $dayNames[$todayDayOfWeek] ?? 'today';
            $actual   = $dayNames[$record->day_of_week] ?? 'unknown';
            throw new \Exception(
                "QR code is only valid on {$actual}. Please use today's ({$expected}) code."
            );
        }

        return $record->person_id;
    }

    /**
     * Get today's QR code for a given person.
     * If QR codes do not exist for this person, auto-generate all 7 codes and return today's.
     *
     * @param  int  $personId
     * @return string|null
     */
    public function getTodayCodeForPerson(int $personId): ?string
    {
        $todayDay = (int) Carbon::now()->format('w'); // 0=Sun, 1=Mon, ..., 6=Sat

        $record = PersonQrCode::where('person_id', $personId)
            ->where('day_of_week', $todayDay)
            ->first();

        if ($record) {
            return $record->code;
        }

        $codes = $this->generateForPerson($personId);
        return $codes[$todayDay] ?? null;
    }

    /**
     * Generate a cryptographically unique code.
     * Format: QR-<random32chars>
     */
    private function makeUniqueCode(): string
    {
        do {
            $code = 'QR-' . strtoupper(Str::random(32));
        } while (PersonQrCode::where('code', $code)->exists());

        return $code;
    }
}

