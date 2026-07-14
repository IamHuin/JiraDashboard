<?php

namespace App\Repositories\Eloquent;

use App\DTO\Manager\ManagerDTO;
use App\Repositories\Interfaces\ManagerInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ManagerRepository implements ManagerInterface
{
    public function getListUsers(ManagerDTO $dto, int $perPage = 10): LengthAwarePaginator
    {
        $query = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->select(
                'users.id', 
                'users.jira_username', 
                'users.jira_display_name', 
                'users.super_admin',
                'roles.id as role_id', 
                'roles.name as role_name'
            )
            ->where('users.jira_username', '!=', 'admin')

            ->when($dto->super_admin !== null, function ($query) use ($dto) {
                $query->where('users.super_admin', $dto->super_admin);
            })

            ->orderBy('users.updated_at', 'desc');
        
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($user) {
            if ($user->super_admin == 1) {
                $user->role_name = 'Super Admin';
            }
            return $user;
        });

        return $paginator;
    }

    public function updateUser(ManagerDTO $dto)
    {
        if ($dto->super_admin === null && $dto->role_id === null) {
            return false;
        }

        $updateData = [];
        if ($dto->super_admin !== null) {
            $updateData['super_admin'] = $dto->super_admin;
        }
        if ($dto->role_id !== null) {
            $updateData['role_id'] = $dto->role_id;
        }

        if (empty($updateData)) {
            return false;
        }

        return DB::table('users')
            ->where('jira_username', $dto->user_name)
            ->update($updateData);
    }
}