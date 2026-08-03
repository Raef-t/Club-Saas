<?php
namespace Modules\ClubManager\Services;

use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Repositories\ClubRepositoryInterface;
use Modules\Core\Exceptions\CannotDeleteException;

class ClubService
{
    protected $repository;

    public function __construct(ClubRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }

    /**
     * Delete a club and all its branches (and all their members, subscriptions, etc.) via CascadeSoftDeletes.
     */
    public function delete($id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $club = Club::findOrFail($id);
            return $club->delete();
        });
    }

    /**
     * Restore a deleted club and all cascaded branches and child records.
     */
    public function restore($id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $club = Club::onlyTrashed()->findOrFail($id);
            return $club->restore();
        });
    }

    /**
     * Permanently delete a club.
     */
    public function forceDelete($id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $club = Club::withTrashed()->findOrFail($id);
            return $club->forceDelete();
        });
    }
}
