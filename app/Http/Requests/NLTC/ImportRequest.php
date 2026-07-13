<?php

namespace App\Http\Requests\NLTC;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file để tải lên.',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls.',
            'file.max' => 'Dung lượng file không được vượt quá 5MB.',
        ];
    }
}
