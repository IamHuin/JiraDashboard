<?php

namespace App\Http\Requests\Overdue;

use Illuminate\Foundation\Http\FormRequest;

class OverdueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_names' => ['nullable', 'array'],
            'project_names.*' => ['string'],
            'period' => ['nullable', 'date_format:m-Y'],
            'user_name' => ['nullable', 'string'],
            'issuetype' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_names.array' => 'Project names phải là một mảng.',
            'project_names.*.string' => 'Mỗi project name trong mảng phải là một chuỗi ký tự.',
            'period.date_format' => 'Period phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'user_name.string' => 'User name phải là một chuỗi ký tự.',
            'issuetype.string' => 'Issue type phải là một chuỗi ký tự.',
            'status.string' => 'Status phải là một chuỗi ký tự.'
        ];
    }
}