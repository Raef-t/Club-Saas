<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Models\User;

class UsernameGeneratorService
{
    /**
     * Custom overrides for specific roles.
     */
    const OVERRIDES = [
        'super_admin'  => 'tec-adm',
        'admin'        => 'tec-adm',
        'coach'        => 'tec-coach',
        'player'       => 'tec-ply',
        'member'       => 'tec-ply',
        'receptionist' => 'tec-rec',
    ];

    /**
     * Generate a unique username for a given role in format: tec-{prefix}-{5_digits}
     * Examples:
     * - coach -> tec-coach-48291
     * - sales -> tec-sal-91932
     * - hr    -> tec-hr-10293
     * - it    -> tec-it-58291
     *
     * @param string $role
     * @return string
     */
    public static function generateForRole(string $role): string
    {
        $normalized = strtolower(trim($role));

        if (isset(self::OVERRIDES[$normalized])) {
            $prefix = self::OVERRIDES[$normalized];
        } else {
            $cleanRole = str_replace(['_', '-'], '', $normalized);
            $shortRole = strlen($cleanRole) > 0 
                ? (strlen($cleanRole) <= 3 ? $cleanRole : substr($cleanRole, 0, 3)) 
                : 'stf';

            $prefix = "tec-{$shortRole}";
        }

        do {
            $digits = sprintf('%05d', mt_rand(10000, 99999));
            $username = "{$prefix}-{$digits}";
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
