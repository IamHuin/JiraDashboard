<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_key' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'project_key.string' => 'Mã dự án phải là một chuỗi ký tự.',
        ];
    }
}