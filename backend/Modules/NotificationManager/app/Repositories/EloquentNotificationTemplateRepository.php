<?php

namespace Modules\NotificationManager\Repositories;

use Modules\NotificationManager\Models\NotificationTemplate;

class EloquentNotificationTemplateRepository implements NotificationTemplateRepositoryInterface
{
    public function all()
    {
        return NotificationTemplate::all();
    }

    public function findBySlug(string $slug)
    {
        return NotificationTemplate::where('slug', $slug)->first();
    }

    public function create(array $data)
    {
        return NotificationTemplate::create($data);
    }
}
