<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static void deleting(\Closure|string $callback)
 * @method static void restored(\Closure|string $callback)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait CascadeSoftDeletes
{
    /**
     * Boot the cascade soft deletes trait for a model.
     */
    protected static function bootCascadeSoftDeletes(): void
    {
        static::deleting(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $cascadeRelations = $model->getCascadeDeletes();
            if (empty($cascadeRelations)) {
                return;
            }

            DB::transaction(function () use ($model, $cascadeRelations) {
                foreach ($cascadeRelations as $relationName) {
                    if (!method_exists($model, $relationName)) {
                        continue;
                    }

                    $relation = $model->{$relationName}();
                    if (!$relation instanceof Relation) {
                        continue;
                    }

                    $results = $model->{$relationName};

                    if ($results instanceof Collection) {
                        foreach ($results as $child) {
                            if ($child instanceof Model && method_exists($child, 'delete')) {
                                $child->delete();
                            }
                        }
                    } elseif ($results instanceof Model) {
                        if (method_exists($results, 'delete')) {
                            $results->delete();
                        }
                    }
                }
            });
        });

        static::restored(function (Model $model) {
            $cascadeRelations = $model->getCascadeDeletes();
            if (empty($cascadeRelations)) {
                return;
            }

            DB::transaction(function () use ($model, $cascadeRelations) {
                foreach ($cascadeRelations as $relationName) {
                    if (!method_exists($model, $relationName)) {
                        continue;
                    }

                    $relation = $model->{$relationName}();
                    if (!$relation instanceof Relation) {
                        continue;
                    }

                    // Query soft deleted children for this relation
                    $query = $relation->onlyTrashed();
                    $trashedChildren = $query->get();

                    foreach ($trashedChildren as $child) {
                        if ($child instanceof Model && method_exists($child, 'restore')) {
                            $child->restore();
                        }
                    }
                }
            });
        });
    }

    /**
     * Get the relationships that should be cascade soft deleted.
     */
    public function getCascadeDeletes(): array
    {
        return property_exists($this, 'cascadeDeletes') && is_array($this->cascadeDeletes)
            ? $this->cascadeDeletes
            : [];
    }
}
