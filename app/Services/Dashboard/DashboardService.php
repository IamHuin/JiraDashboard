<?php

namespace App\Services\Dashboard;

use App\Repositories\Interfaces\DashboardInterface;
use Carbon\Carbon;

class DashboardService
{
    protected DashboardInterface $repo;

    public function __construct(DashboardInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getOverview(?string $periodStart, ?string $periodEnd, ?array $projectNames = []): array
    {
        $period = $this->normalizePeriod($periodStart, $periodEnd);

        return [
            'success' => true,
            'data'    => $this->repo->getOverview([
                'project_names' => $projectNames,
                'period_start'  => $period['start_date'],
                'period_end'    => $period['end_date'],
            ]),
        ];
    }

    public function getBugRatio(?string $periodStart, ?string $periodEnd, ?string $userName, ?array $projectNames = []): array
    {
        $period = $this->normalizePeriod($periodStart, $periodEnd);

        $issues = ($period['start_date'] && $period['end_date'])
            ? $this->repo->getIssuesByPeriod($period['start_date'], $period['end_date'], $projectNames, $userName)
            : [];

        return [
            'success' => true,
            'data'    => [
                'user'         => $userName,
                'project_names'=> $projectNames,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'start_date'   => $period['start_date'],
                'end_date'     => $period['end_date'],
                'issues'       => $issues,
            ],
        ];
    }
    
    protected function normalizePeriod(?string $periodStart, ?string $periodEnd): array
    {
        $start = null;
        $end   = null;

        if ($periodStart) {
            $carbonStart = Carbon::createFromFormat('m-Y', $periodStart);
            $start = $carbonStart->startOfMonth()->format('Y-m-d H:i:s');
            $end   = $periodEnd
                ? Carbon::createFromFormat('m-Y', $periodEnd)->endOfMonth()->format('Y-m-d H:i:s')
                : $carbonStart->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($periodEnd) {
            $carbonEnd = Carbon::createFromFormat('m-Y', $periodEnd);
            $end = $carbonEnd->endOfMonth()->format('Y-m-d H:i:s');
        }

        return [
            'start_date' => $start,
            'end_date'   => $end,
        ];
    }
}
