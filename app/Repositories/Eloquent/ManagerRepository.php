<?php

namespace App\Repositories\Eloquent;

use App\DTO\Manager\ManagerDTO;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Interfaces\ManagerInterface;

class ManagerRepository implements ManagerInterface
{
    /**
     * Lấy danh sách Users kèm thông tin Role (Đã sửa lỗi Eager Loading)
     */
    public function getListUsers(ManagerDTO $dto)
    {
        return User::query()
            ->with([
                'role' => function ($query) {
                    $query->select('id', 'name');
                }
            ])
            ->select('id', 'jira_username', 'jira_display_name', 'role_id')
            ->where('super_admin', 0)

            ->when($dto->user_name, function ($query, $userName) {
                $query->where('jira_display_name', 'like', "%{$userName}%");
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Cập nhật Role cho User (Đã sửa gán theo ID truyền từ Route)
     * Thêm tham số $id nhận từ Controller truyền xuống
     */
    public function updateUser(ManagerDTO $dto, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        $role = Role::find($dto->role_id);

        if (!$role) {
            return false;
        }

        $roleProjects = $role->jira_projects_json;

        if (empty($roleProjects) || !is_array($roleProjects)) {
            $roleProjects = [];
        }

        return $user->update([
            'role_id'                 => $dto->role_id,
            'jira_projects_role_json' => json_encode($roleProjects)
        ]);
    }
}