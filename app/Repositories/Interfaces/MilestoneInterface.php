<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MilestoneInterface
{
    public function getMilestones(string $period, ?string $reportType = null, ?string $ticket_code = null, ?array $projectNames = [], int $perPage = 10): LengthAwarePaginator;
}