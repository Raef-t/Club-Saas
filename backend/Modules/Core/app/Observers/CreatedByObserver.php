<?php

namespace Modules\Core\Observers;

use Illuminate\Support\Facades\Auth;

class CreatedByObserver
{
    public function creating($model)
    {
        if (Auth::check() && empty($model->created_by)) {
            $model->created_by = Auth::id();
        }
    }
}
