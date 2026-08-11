<?php

namespace Modules\SubscriptionManager\Enums;

enum PlayerSubscriptionStatus: string
{
    case ACTIVE = 'active';
    case FINISHED = 'finished';
    case FROZEN = 'frozen';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'فعال',
            self::FINISHED => 'منتهي',
            self::FROZEN => 'مجمد',
            self::TERMINATED => 'تم إنهاؤه من الإدارة',
        };
    }
}
