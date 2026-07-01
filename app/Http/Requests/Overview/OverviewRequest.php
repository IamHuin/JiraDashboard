<?php

namespace App\Http\Requests\Overview;

use Illuminate\Foundation\Http\FormRequest;

class OverviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'date_format:m-Y'],
            'project_names' => ['nullable', 'array'],
            'project_names.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.date_format' => 'Period phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'project_names.array' => 'Project names phải là một mảng.',
            'project_names.*.string' => 'Mỗi project name phải là chuỗi ký tự.',
        ];
    }
}