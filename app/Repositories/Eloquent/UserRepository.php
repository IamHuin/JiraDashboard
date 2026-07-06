<?php

namespace App\Repositories\Eloquent;

use App\DTO\Auth\AuthDTO;
use App\Models\User;
use App\Repositories\Interfaces\UserInterface;
use Illuminate\Support\Facades\Crypt;

class UserRepository implements UserInterface
{

    public function updateOrCreateByJira(AuthDTO $dto, array $userData): User
    {
        return User::updateOrCreate(
            ['jira_username' => $dto->jira_username],
            [
                'jira_password' => Crypt::encryptString($dto->jira_password),
                'jira_display_name' => $userData['displayName'] ?? 'unknown',
                'jira_email' => $userData['emailAddress'] ?? 'unknown',
            ]
        );
    }
}