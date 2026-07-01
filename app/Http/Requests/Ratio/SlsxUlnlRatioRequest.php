<?php

namespace App\Http\Requests\Ratio;

use Illuminate\Foundation\Http\FormRequest;

class SlsxUlnlRatioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_names' => ['nullable', 'array'],
            'project_names.*' => ['string'],
            'period' => ['nullable', 'date_format:m-Y'],
            'user_name' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.date_format' => 'Period phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'user_name.string' => 'User name phải là chuỗi ký tự.',
            'project_names.array' => 'Project names phải là một mảng.',
            'project_names.*.string' => 'Mỗi project name phải là chuỗi ký tự.',
        ];
    }
}