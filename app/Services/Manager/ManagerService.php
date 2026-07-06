<?php

namespace App\Services\Manager;

use App\DTO\Manager\ManagerDTO;
use App\Repositories\Interfaces\ManagerInterface;

class ManagerService
{
    public function __construct(protected ManagerInterface $managerRepo) {}

    public function getListUsers(ManagerDTO $dto)
    {
        return $this->managerRepo->getListUsers($dto);
    }
}