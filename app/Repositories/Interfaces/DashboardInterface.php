<?php

namespace App\Repositories\Interfaces;

interface DashboardInterface
{
    public function getOverview(array $filters): array;

    public function getIssuesByPeriod(string $start, string $end): array;
}