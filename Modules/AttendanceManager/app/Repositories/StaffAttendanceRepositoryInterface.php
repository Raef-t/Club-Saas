<?php

namespace Modules\AttendanceManager\Repositories;

interface StaffAttendanceRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findOpenAttendance($staffId);
    public function getHistory($staffId, $from = null, $to = null);
}
