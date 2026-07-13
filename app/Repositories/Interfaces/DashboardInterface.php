<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DashboardInterface
{
    public function getOverview(array $filters): array;

    public function getBugRatioByPeriod(string $period, ?array $projectNames = [], ?int $perPage = null): LengthAwarePaginator;

    public function getSlsxUlnlRatioByPeriod(string $period, ?array $projectNames = [], ?int $perPage = null): LengthAwarePaginator;
    
    public function getOverdueIssues(string $period, ?array $projectNames = [], ?string $issueType = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;
    
    public function getOverdueLogWork(string $period, ?array $projectNames = [], ?string $issueType = null, ?string $statusLogWork = null, int $perPage = 10): LengthAwarePaginator;
}