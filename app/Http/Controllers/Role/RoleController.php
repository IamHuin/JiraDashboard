<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;
use App\Services\Role\RoleService;
use Illuminate\Support\Facades\Log;
use Exception;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roleService) {}

    /**
     * Lấy danh sách tất cả các Roles
     */
    public function getListRoles()
    {
        try {
            $roles = $this->roleService->getListRoles();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách vai trò thành công.',
                'data'    => $roles
            ], 200);
        } catch (Exception $e) {
            Log::error("Get List Roles Failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách vai trò.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * Tạo mới một Role (Kèm gán quyền và dự án tích chọn)
     */
    public function createRole(RoleRequest $request)
    {
        try {
            $role = $this->roleService->createRole($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tạo vai trò mới thành công.',
                'data'    => $role
            ], 201);
        } catch (Exception $e) {
            Log::error("Create Role Failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo vai trò mới.',
                'data'    => null
            ], 500);
        }
    }

    /**
     * Xem chi tiết một Role
     */
    public function getDetailRole($id)
    {
        try {
            $role = $this->roleService->getDetailRole($id);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy vai trò yêu cầu.',
                    'data'    => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết vai trò thành công.',
                'data'    => $role
            ], 200);
        } catch (Exception $e) {
            Log::error("Get Detail Role Failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy chi tiết vai trò.',
                'data'    => null
            ], 500);
        }
    }

    /**
     * Cập nhật/Sửa thông tin Role
     */
    public function updateRole(RoleRequest $request, $id)
    {
        try {
            $updated = $this->roleService->updateRole($id, $request->validated());

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy vai trò để cập nhật.',
                    'data'    => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin vai trò thành công.',
                'data'    => $updated
            ], 200);
        } catch (Exception $e) {
            Log::error("Update Role Failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật vai trò thất bại.',
                'data'    => null
            ], 500);
        }
    }

    /**
     * Xóa Role khỏi hệ thống
     */
    public function deleteRole($id)
    {
        try {
            $deleted = $this->roleService->deleteRole($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy vai trò để xóa hoặc vai trò không thể xóa.',
                    'data'    => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xóa vai trò thành công.',
                'data'    => null
            ], 200);
        } catch (Exception $e) {
            Log::error("Delete Role Failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Xóa vai trò thất bại.',
                'data'    => null
            ], 500);
        }
    }
}