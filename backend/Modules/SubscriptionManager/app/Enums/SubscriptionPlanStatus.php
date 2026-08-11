<?php

namespace Modules\SubscriptionManager\Enums;

enum SubscriptionPlanStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'نشطة',
            self::INACTIVE => 'غير نشطة',
            self::COMPLETED => 'مكتملة',
        };
    }
}
