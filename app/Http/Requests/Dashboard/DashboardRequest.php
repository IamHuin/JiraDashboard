<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_names' => ['nullable', 'array'],
            'project_names.*' => ['string'],
            'period' => ['required', 'date_format:m-Y'],
            'user_name' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Period là bắt buộc.',
            'period.date_format' => 'Period phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'project_names.array' => 'Project names phải là một mảng.',
            'project_names.*.string' => 'Mỗi project name phải là chuỗi ký tự.',
            'user_name.string' => 'User name phải là một chuỗi ký tự.',
        ];
    }
}
