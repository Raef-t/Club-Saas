<?php

namespace Modules\Authentication\Services;

use Illuminate\Support\Str;
use Modules\Authentication\Models\User;

class UsernameSuggestionService
{
    /**
     * Regex pattern for valid custom usernames:
     * - Must start and end with an alphanumeric character (English or Arabic)
     * - May contain letters (English/Arabic), numbers, underscores, dots, and hyphens in between
     * - Length between 3 and 30 characters
     */
    const USERNAME_REGEX = '/^[a-zA-Z0-9\p{Arabic}](?:[a-zA-Z0-9\p{Arabic}_.-]{1,28}[a-zA-Z0-9\p{Arabic}])?$/u';

    /**
     * Check if a username format is valid.
     *
     * @param string $username
     * @return bool
     */
    public static function isValidFormat(string $username): bool
    {
        $username = trim($username);
        $len = mb_strlen($username, 'UTF-8');
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
        if (mb_strlen($base, 'UTF-8') < 3) {
            if (!empty($fullName)) {
                $base = self::sanitizeBase($fullName);
            }
            if (mb_strlen($base, 'UTF-8') < 3) {
                $base = 'user';
            }
        }

        // Ensure base leaves room for suffixes (max total length is 30)
        if (mb_strlen($base, 'UTF-8') > 22) {
            $base = mb_substr($base, 0, 22, 'UTF-8');
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
            if (mb_strlen($nameSlug, 'UTF-8') >= 3 && $nameSlug !== $base) {
                $candidates[] = $nameSlug . '_' . mt_rand(10, 99);
                $candidates[] = $nameSlug . date('Y');
            }
        }

        // Filter, validate, deduplicate, and check database availability
        $suggestions = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $candidate = mb_strtolower(trim($candidate), 'UTF-8');

            // Truncate to 30 characters if needed
            if (mb_strlen($candidate, 'UTF-8') > 30) {
                $candidate = mb_substr($candidate, 0, 30, 'UTF-8');
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
            $candidate = mb_substr($base, 0, 24, 'UTF-8') . '_' . $randSuffix;
            if (self::isValidFormat($candidate) && self::isAvailable($candidate, $ignoreUserId) && !in_array($candidate, $suggestions)) {
                $suggestions[] = $candidate;
            }
        }

        return array_values($suggestions);
    }

    /**
     * Sanitize an input string to be a valid base username.
     * Preserves Arabic and alphanumeric characters while stripping invalid symbols.
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

        // Convert spaces to underscores
        $slug = preg_replace('/\s+/u', '_', $input);

        // Remove any characters not in [a-zA-Z0-9\p{Arabic}_.-]
        $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9_.-]/u', '', $slug);

        // Remove consecutive dots, dashes, or underscores
        $slug = preg_replace('/[._-]{2,}/u', '_', $slug);

        // Trim leading and trailing dots, dashes, underscores
        $slug = trim($slug, '._-');

        return mb_strtolower($slug, 'UTF-8');
    }
}
