<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class ManagerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'super_admin' => 'nullable|boolean',
            'user_name' => ['nullable', 'string'],
            'role_id'   => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'super_admin.boolean' => 'Trường quyền Super Admin phải là kiểu logic.',
            'user_name.nullable' => 'Tên người dùng là bắt buộc.',
            'user_name.string' => 'Tên người dùng phải là một chuỗi ký tự.',
            'role_id.integer'  => 'ID vai trò không hợp lệ.',
            'role_id.exists'   => 'Vai trò này không tồn tại trong hệ thống.',
        ];
    }
}