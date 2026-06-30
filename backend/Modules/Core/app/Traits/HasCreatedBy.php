<?php

namespace Modules\Core\Traits;

use Modules\Authentication\Models\User;
use Modules\Core\Observers\CreatedByObserver;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void observe(string|object $class)
 */
trait HasCreatedBy
{
    public static function bootHasCreatedBy()
    {
        static::observe(CreatedByObserver::class);
    }

    public function creator()
    {
        // Reference to the User model in Authentication module
        return $this->belongsTo(User::class, 'created_by');
    }
}
