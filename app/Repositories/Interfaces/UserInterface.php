<?php

namespace App\Repositories\Interfaces;

use App\DTO\Auth\AuthDTO;
use App\Models\User;

interface UserInterface
{
    public function updateOrCreateByJira(AuthDTO $dto, array $userData): User;
}