<?php

namespace App\Repositories\Interfaces;

interface DashboardInterface
{
    public function getOverview(array $filters): array;
    public function getBugRatioByPeriod(string $start, string $end): array;
    public function getSlsxUlnlRatioByPeriod(string $start, string $end): array;
}