<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Interfaces\RoleInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class RoleRepository implements RoleInterface
{
    /**
     * Lấy danh sách tất cả các Roles
     */
    public function getListRoles()
    {
        return Role::query()->select('id', 'name')->orderBy('id', 'desc')->get();
    }

    /**
     * Xem chi tiết một Role
     */
    public function getDetailRole($id)
    {
        return Role::find($id);
    }

    /**
     * Tạo mới một Role và lưu mảng permission dưới dạng JSON
     */
    public function createRole(array $data)
    {
        try {
            $permissionJson = isset($data['permissions']) ? json_encode($data['permissions']) : json_encode([]);

            $role = Role::create([
                'name'            => $data['name'],
                'permission_json' => $permissionJson
            ]);

            return $role;
        } catch (Exception $e) {
            Log::error("Repository Create Role Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin Role và chuỗi JSON permission
     */
    public function updateRole($id, array $data)
    {
        try {
            $role = Role::find($id);
            if (!$role) {
                return null;
            }

            $updateData = [
                'name' => $data['name']
            ];

            if (isset($data['permissions'])) {
                $updateData['permission_json'] = json_encode($data['permissions']);
            }

            $role->update($updateData);

            return $role;
        } catch (Exception $e) {
            Log::error("Repository Update Role Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Xóa thẳng Role
     */
    public function deleteRole($id)
    {
        try {
            $role = Role::find($id);
            if (!$role) {
                return false;
            }

            $role->delete();
            return true;
        } catch (Exception $e) {
            Log::error("Repository Delete Role Error: " . $e->getMessage());
            throw $e;
        }
    }
}