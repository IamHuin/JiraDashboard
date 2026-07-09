<?php

use App\Enums\PaginateEnum;

if (!function_exists('parse_json_to_array')) {
    /**
     * Ép mọi định dạng dữ liệu (JSON string, Array, Null, Trống) về mảng phẳng sạch sẽ
     */
    function parse_json_to_array($data): array
    {
        if (empty($data)) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            return json_decode($data, true) ?? [];
        }

        return [];
    }
}

if (!function_exists('format_dashboard_success')) {
    function format_dashboard_success(?string $userName, array $allowedProjectNames, ?string $period, array $issues): array
    {
        return [
            'success' => true,
            'data' => [
                'user' => $userName,
                'project_names' => $allowedProjectNames,
                'period' => $period,
                'issues' => $issues,
            ],
        ];
    }
}

if (!function_exists('format_dashboard_empty')) {
    function format_dashboard_empty(?string $userName, ?string $period, int $page = PaginateEnum::DEFAULT_PAGE, int $perPage = PaginateEnum::DEFAULT_PER_PAGE): array
    {
        return [
            'success' => true,
            'data' => [
                'user' => $userName,
                'project_names' => [],
                'period' => $period,
                'issues' => [
                    'ratio' => [],
                    'details' => [
                        'list' => [],
                        'meta' => [
                            'total' => 0,
                            'per_page' => $perPage,
                            'current_page' => $page,
                            'last_page' => 1
                        ]
                    ]
                ],
            ],
        ];
    }
}