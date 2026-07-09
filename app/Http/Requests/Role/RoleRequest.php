<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

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
            'project_keys'   => ['required', 'array'],
            'project_keys.*' => ['string'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required'         => 'Tên vai trò không được để trống.',
            'name.string'           => 'Tên vai trò phải là chuỗi ký tự.',
            'name.unique'           => 'Tên vai trò này đã tồn tại trong hệ thống.',
            'project_keys.required' => 'Danh sách mã dự án không được để trống.',
            'project_keys.array'    => 'Danh sách mã dự án phải thuộc định dạng mảng.',
            'project_keys.*.string' => 'Mã dự án bên trong mảng phải là chuỗi ký tự.',
        ];
    }
}