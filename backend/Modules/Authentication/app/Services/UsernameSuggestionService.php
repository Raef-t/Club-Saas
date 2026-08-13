<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Str;
use Modules\Authentication\Models\User;

class UsernameSuggestionService
{
    /**
     * Regex pattern for valid custom usernames:
     * - Must start and end with an alphanumeric character
     * - May contain letters, numbers, underscores, dots, and hyphens in between
     * - Length between 3 and 30 characters
     */
    const USERNAME_REGEX = '/^[a-zA-Z0-9](?:[a-zA-Z0-9_.-]{1,28}[a-zA-Z0-9])?$/';

    /**
     * Check if a username format is valid.
     *
     * @param string $username
     * @return bool
     */
    public static function isValidFormat(string $username): bool
    {
        $len = strlen($username);
        if ($len < 3 || $len > 30) {
            return false;
        }

        return (bool) preg_match(self::USERNAME_REGEX, $username);
    }

    /**
     * Check if a username is available (not used in username or custom_username).
     *
     * @param string $username
     * @param int|null $ignoreUserId
     * @return bool
     */
    public static function isAvailable(string $username, ?int $ignoreUserId = null): bool
    {
        $username = trim($username);
        if (empty($username)) {
            return false;
        }

        return !User::where(function ($query) use ($username) {
            $query->where('username', $username)
                  ->orWhere('custom_username', $username);
        })
        ->when($ignoreUserId, function ($query, $ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        })
        ->exists();
    }

    /**
     * Generate unique and valid username suggestions based on input or full name.
     *
     * @param string $input
     * @param string|null $fullName
     * @param int|null $ignoreUserId
     * @param int $limit
     * @return array
     */
    public static function generateSuggestions(
        string $input,
        ?string $fullName = null,
        ?int $ignoreUserId = null,
        int $limit = 5
    ): array {
        $base = self::sanitizeBase($input);

        // If input base is empty or too short, fallback to full name or default
        if (strlen($base) < 3) {
            if (!empty($fullName)) {
                $base = self::sanitizeBase($fullName);
            }
            if (strlen($base) < 3) {
                $base = 'user';
            }
        }

        // Ensure base leaves room for suffixes (max total length is 30)
        if (strlen($base) > 22) {
            $base = substr($base, 0, 22);
            $base = rtrim($base, '._-');
        }

        $candidates = [];

        // Strategy 1: Sequential & Direct numbers based on input (highest priority)
        $candidates[] = $base . '_' . 1;
        $candidates[] = $base . '_' . 2;
        $candidates[] = $base . '_' . date('Y');
        $candidates[] = $base . date('Y');
        $candidates[] = $base . mt_rand(10, 99);
        $candidates[] = $base . '_' . mt_rand(10, 99);
        $candidates[] = $base . '.' . mt_rand(10, 99);

        // Strategy 2: Suffixes based on input
        $candidates[] = $base . '_gym';
        $candidates[] = $base . '_club';
        $candidates[] = $base . '_pro';
        $candidates[] = $base . '_vip';

        // Strategy 3: Prefixes based on input
        $candidates[] = 'the_' . $base;
        $candidates[] = 'real_' . $base;

        // Strategy 4: Additional fallback numbers based on input
        for ($i = 3; $i <= 20; $i++) {
            $candidates[] = $base . '_' . $i;
            $candidates[] = $base . $i;
        }

        // Strategy 5: Name-based variations ONLY as a last resort if full_name is available
        if (!empty($fullName)) {
            $nameSlug = self::sanitizeBase($fullName);
            if (strlen($nameSlug) >= 3 && $nameSlug !== $base) {
                $candidates[] = $nameSlug . '_' . mt_rand(10, 99);
                $candidates[] = $nameSlug . date('Y');
            }
        }

        // Filter, validate, deduplicate, and check database availability
        $suggestions = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim($candidate));

            // Truncate to 30 characters if needed
            if (strlen($candidate) > 30) {
                $candidate = substr($candidate, 0, 30);
                $candidate = rtrim($candidate, '._-');
            }

            if (isset($seen[$candidate])) {
                continue;
            }
            $seen[$candidate] = true;

            if (self::isValidFormat($candidate) && self::isAvailable($candidate, $ignoreUserId)) {
                $suggestions[] = $candidate;
                if (count($suggestions) >= $limit) {
                    break;
                }
            }
        }

        // If we still need more suggestions, generate randomized ones based on input base
        while (count($suggestions) < $limit) {
            $randSuffix = mt_rand(100, 9999);
            $candidate = substr($base, 0, 24) . '_' . $randSuffix;
            if (self::isValidFormat($candidate) && self::isAvailable($candidate, $ignoreUserId) && !in_array($candidate, $suggestions)) {
                $suggestions[] = $candidate;
            }
        }

        return array_values($suggestions);
    }

    /**
     * Sanitize an input string to be a valid base username.
     * Handles Arabic transliteration and strips invalid characters.
     *
     * @param string $input
     * @return string
     */
    public static function sanitizeBase(string $input): string
    {
        $input = trim($input);
        if (empty($input)) {
            return '';
        }

        // Transliterate Arabic to English phonetic approximation if needed
        $transliterated = self::transliterateArabic($input);

        // Convert spaces to underscores
        $slug = preg_replace('/\s+/', '_', $transliterated);

        // Remove any characters not in [a-zA-Z0-9_.-]
        $slug = preg_replace('/[^a-zA-Z0-9_.-]/', '', $slug);

        // Remove consecutive dots, dashes, or underscores
        $slug = preg_replace('/[._-]{2,}/', '_', $slug);

        // Trim leading and trailing dots, dashes, underscores
        $slug = trim($slug, '._-');

        return strtolower($slug);
    }

    /**
     * Arabic transliteration map for generating friendly English slugs from Arabic names.
     *
     * @param string $text
     * @return string
     */
    protected static function transliterateArabic(string $text): string
    {
        $map = [
            'أ' => 'a', 'إ' => 'e', 'آ' => 'aa', 'ا' => 'a',
            'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j',
            'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh',
            'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh',
            'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z',
            'ع' => 'a', 'غ' => 'gh', 'ف' => 'f', 'ق' => 'q',
            'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
            'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a',
            'ة' => 'h', 'ء' => '', 'ئ' => 'e', 'ؤ' => 'o',
            'پ' => 'p', 'چ' => 'ch', 'ڤ' => 'v', 'گ' => 'g',
        ];

        return strtr($text, $map);
    }
}
