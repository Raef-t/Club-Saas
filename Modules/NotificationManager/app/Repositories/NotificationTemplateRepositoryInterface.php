<?php

namespace Modules\NotificationManager\Repositories;

use Modules\NotificationManager\Models\NotificationTemplate;

interface NotificationTemplateRepositoryInterface
{
    public function all();
    public function findBySlug(string $slug);
    public function create(array $data);
}
