<?php

namespace App\Http\Requests\Overview;

use Illuminate\Foundation\Http\FormRequest;

class OverviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_names'   => ['nullable', 'array'],
            'project_names.*' => ['string'],
            'period_start'    => ['nullable', 'date_format:m-Y', 'before_or_equal:period_end'],
            'period_end'      => ['nullable', 'date_format:m-Y', 'after_or_equal:period_start'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_start.date_format'   => 'Period start phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 05-2026).',
            'period_start.before_or_equal' => 'Period start phải nhỏ hơn hoặc bằng period end.',
            'period_end.date_format'     => 'Period end phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'period_end.after_or_equal'  => 'Period end phải lớn hơn hoặc bằng period start.',
            'project_names.array'        => 'Project names phải là một mảng.',
            'project_names.*.string'     => 'Mỗi project name phải là chuỗi ký tự.',
        ];
    }
}
