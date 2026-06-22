<?php

namespace App\Services\Dashboard;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginationService
{
    public function format(LengthAwarePaginator $paginator): array
    {
        return [
            'list' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ];
    }
}