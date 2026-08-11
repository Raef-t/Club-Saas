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

    public function delete(int $id, string $confirmation = ''): void
    {
        if (strtolower(trim($confirmation)) !== 'delete') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirmation' => __('سيتم حذف هذا النادي بالكامل مع كافة الفروع والمشتركين والمدربين والاشتراكات المتعلقة به، هل أنت متأكد؟ أرسل "delete" للتأكيد.')
            ]);
        }

        $club = Club::findOrFail($id);
        $club->delete();
    }

    public function getTrashed()
    {
        return $this->repository->getTrashed();
    }

    public function restoreClub($id)
    {
        return $this->repository->restore($id);
    }
}
