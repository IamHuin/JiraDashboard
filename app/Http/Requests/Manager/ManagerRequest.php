<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class ManagerRequest extends FormRequest
{
    /**
     * Quy tắc validate dữ liệu (Tự động thay đổi theo từng Route API)
     */
    public function rules(): array
    {
        $rules = [
            'user_name' => ['nullable', 'string']
        ];

        if ($this->route()->getActionMethod() === 'updateUser') {
            $rules['role_id'] = ['required', 'integer', 'exists:roles,id'];
        }

        return $rules;
    }

    /**
     * Thông báo lỗi trả về bằng tiếng Việt
     */
    public function messages(): array
    {
        return [
            'user_name.string' => 'Tên người dùng phải là một chuỗi ký tự.',
            'role_id.required' => 'Vui lòng chọn vai trò (Role) cho người dùng.',
            'role_id.integer' => 'Mã vai trò phải là một số nguyên.',
            'role_id.exists' => 'Vai trò được chọn không tồn tại trên hệ thống.',
        ];
    }
}