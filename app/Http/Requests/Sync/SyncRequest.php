<?php

namespace App\Http\Requests\Sync;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_names' => ['nullable', 'array'],
            'project_names.*' => ['string'],
            'period_from' => ['required', 'date_format:m-Y'],
            'period_to' => [
                'required',
                'date_format:m-Y',
                function ($attribute, $value, $fail) {
                    $from = $this->input('period_from');
                    if (!$from) return;

                    try {
                        $fromDate = Carbon::createFromFormat('m-Y', $from)->startOfMonth();
                        $toDate = Carbon::createFromFormat('m-Y', $value)->startOfMonth();

                        if ($fromDate->gt($toDate)) {
                            $fail('Tháng bắt đầu không được lớn hơn tháng kết thúc.');
                        }
                    } catch (Exception $e) {
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period_from.required' => 'Tháng bắt đầu là bắt buộc.',
            'period_to.required' => 'Tháng kết thúc là bắt buộc.',
            'period_from.date_format' => 'Period phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'period_to.date_format' => 'Period phải là tháng-năm hợp lệ theo định dạng m-Y (ví dụ: 06-2026).',
            'project_names.array' => 'Project names phải là một mảng.',
            'project_names.*.string' => 'Mỗi project name phải là chuỗi ký tự.',
        ];
    }
}
