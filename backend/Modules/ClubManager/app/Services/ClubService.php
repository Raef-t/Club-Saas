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

    public function getAll(array $filters = []) { return $this->repository->all($filters); }
    public function getById($id) { return $this->repository->find($id); }

    public function create(array $data)
    {
        if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $data['logo_url'] = $data['logo']->store('clubs/logos', 'public');
            unset($data['logo']);
        }

        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function updateClubLogo($id, \Illuminate\Http\UploadedFile $logo)
    {
        $club = $this->repository->find($id);

        $rawOldLogo = $club->getRawOriginal('logo_url');
        if ($rawOldLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($rawOldLogo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($rawOldLogo);
        }

        $path = $logo->store('clubs/logos', 'public');
        $club->update(['logo_url' => $path]);

        return $club->fresh();
    }

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

    public function getTrashed(array $filters = [])
    {
        return $this->repository->getTrashed($filters);
    }

    public function restoreClub($id)
    {
        return $this->repository->restore($id);
    }
}
