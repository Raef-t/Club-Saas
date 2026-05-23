<?php

namespace Modules\AttendanceManager\Repositories;

interface MemberAttendanceRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function findOpenAttendance($memberId);
    public function getHistory($memberId, $from = null, $to = null);
}
