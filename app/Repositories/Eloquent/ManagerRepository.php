<?php

namespace App\Repositories\Eloquent;

use App\DTO\Manager\ManagerDTO;
use App\Repositories\Interfaces\ManagerInterface;
use Illuminate\Support\Facades\DB;

class ManagerRepository implements ManagerInterface
{
    public function getListUsers(ManagerDTO $dto)
    {
        return DB::table('users')
            ->select('id', 'jira_username', 'jira_display_name', 'jira_email', 'is_admin')
            ->where('super_admin', 0)

            ->when($dto->email, function ($query, $email) {
                $query->where('jira_email', 'like', "%{$email}%");
            })

            ->when($dto->is_admin !== null, function ($query) use ($dto) {
                $query->where('is_admin', $dto->is_admin);
            })

            ->when($dto->user_name, function ($query, $userName) {
                $query->where('jira_display_name', 'like', "%{$userName}%");
            })
            ->get();
    }
}