<?php

namespace App\Repositories\Interfaces;

use App\DTO\Manager\ManagerDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ManagerInterface
{
    public function getListUsers(ManagerDTO $dto, int $perPage = 10):LengthAwarePaginator;
    public function updateUser(ManagerDTO $dto);
}