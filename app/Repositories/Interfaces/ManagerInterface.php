<?php

namespace App\Repositories\Interfaces;

use App\DTO\Manager\ManagerDTO;

interface ManagerInterface
{
    public function getListUsers(ManagerDTO $dto);
    public function updateUser(ManagerDTO $dto);
}