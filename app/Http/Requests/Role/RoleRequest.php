<?php

namespace App\Http\Requests\Role;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Khai báo các quy tắc validate dữ liệu đầu vào
     */
    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $roleId ? 'unique:roles,name,' . $roleId : 'unique:roles,name'
            ],
            'permissions' => ['required', 'array'],
            'permissions.*' => [
                'string',
                Rule::in(PermissionEnum::getAll())
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required'         => 'Tên vai trò không được để trống.',
            'name.string'           => 'Tên vai trò phải là chuỗi ký tự.',
            'name.unique'           => 'Tên vai trò này đã tồn tại trong hệ thống.',
            'permissions.required' => 'Danh sách quyền không được để trống.',
            'permissions.array' => 'Danh sách quyền phải thuộc định dạng mảng.',
            'permissions.*.string' => 'Quyền bên trong mảng phải là chuỗi ký tự.',
        ];
    }
}