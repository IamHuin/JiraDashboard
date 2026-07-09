<?php

namespace App\Services\Role;

use App\Repositories\Interfaces\RoleInterface;

class RoleService
{
    // Inject Interface vào Service thông qua Constructor Promotion
    public function __construct(protected RoleInterface $roleRepository) {}

    /**
     * Lấy danh sách tất cả các Roles
     */
    public function getListRoles()
    {
        return $this->roleRepository->getListRoles();
    }

    /**
     * Tạo mới một Role (Lưu mảng project dưới dạng JSON)
     */
    public function createRole(array $data)
    {
        return $this->roleRepository->createRole($data);
    }

    /**
     * Xem chi tiết một Role
     */
    public function getDetailRole($id)
    {
        return $this->roleRepository->getDetailRole($id);
    }

    /**
     * Cập nhật thông tin Role và chuỗi JSON project
     */
    public function updateRole($id, array $data)
    {
        return $this->roleRepository->updateRole($id, $data);
    }

    /**
     * Xóa thẳng Role
     */
    public function deleteRole($id)
    {
        return $this->roleRepository->deleteRole($id);
    }
}