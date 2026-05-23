<?php
namespace Modules\MemberManager\Repositories;

interface MemberMeasurementRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
