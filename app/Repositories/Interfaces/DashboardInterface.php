<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DashboardInterface
{
    public function getOverview(array $filters): array;

    public function getBugRatioByPeriod(string $period, ?array $projectNames = [], ?string $userName = null, ?int $perPage = null): array|LengthAwarePaginator;

    public function getSlsxUlnlRatioByPeriod(string $period, ?array $projectNames = [], ?string $userName = null, ?int $perPage = null): array|LengthAwarePaginator;

    public function getListDetail(?string $period, ?string $username = null, ?string $issueType = null, ?array $projectNames = [], int $perPage = 10): LengthAwarePaginator;

    public function getOverdue(?string $period, ?array $projectNames = [], ?string $username = null, ?string $issueType = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;
}