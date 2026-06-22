<?php

namespace App\Repositories\Interfaces;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DashboardInterface
{
    public function getOverview(array $filters): array;
    public function getBugRatioByPeriod(string $start, string $end): array;
    public function getSlsxUlnlRatioByPeriod(string $start, string $end): array;
    public function getListDetail(?string $periodStart, ?string $periodEnd, ?string $username = null, ?string $issueType = null, ?array $projectNames = [], int     $perPage = 10): LengthAwarePaginator;
}