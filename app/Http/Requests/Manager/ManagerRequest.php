<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;

class ManagerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'nullable|email',
            'is_admin' => 'nullable|boolean',
            'user_name' => ['nullable', 'string']
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'is_admin.boolean' => 'Trường quyền admin phải là kiểu logic',
            'user_name.string' => 'Tên người dùng phải là một chuỗi ký tự.',
        ];
    }
}