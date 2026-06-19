<?php

namespace App\Services\Dashboard;

use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\ProjectInterface;
use Carbon\Carbon;

class DashboardService
{
    protected DashboardInterface $dashboardRepo;
    protected ProjectInterface $projectRepo;

    public function __construct(DashboardInterface $dashboardRepo, ProjectInterface $projectRepo)
    {
        $this->dashboardRepo = $dashboardRepo;
        $this->projectRepo = $projectRepo;
    }

    public function getOverview(?string $periodStart, ?string $periodEnd, ?array $projectNames = []): array
    {
        $period = $this->normalizePeriod($periodStart, $periodEnd);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return [
                'success' => true,
                'data' => [],
            ];
        }

        return [
            'success' => true,
            'data' => $this->dashboardRepo->getOverview([
                'project_names' => $allowedProjectNames,
                'period_start'  => $period['start_date'],
                'period_end'    => $period['end_date'],
            ]),
        ];
    }

    public function getBugRatio(?string $periodStart, ?string $periodEnd, ?string $userName, ?array $projectNames = []): array
    {
        $period = $this->normalizePeriod($periodStart, $periodEnd);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        $issues = ($period['start_date'] && $period['end_date'] && !empty($allowedProjectNames))
            ? $this->dashboardRepo->getIssuesByPeriod($period['start_date'], $period['end_date'], $allowedProjectNames, $userName)
            : [];

        return [
            'success' => true,
            'data'    => [
                'user'         => $userName,
                'project_names' => $allowedProjectNames,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'start_date'   => $period['start_date'],
                'end_date'     => $period['end_date'],
                'issues'       => $issues,
            ],
        ];
    }

    public function getProjects(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not authenticated',
                'data' => []
            ];
        }

        $projects = $this->projectRepo->getProjectsJson($user->id);

        return [
            'success' => true,
            'data' => $projects ?? [],
        ];
    }

    protected function filterAllowedProjects(?array $requestProjectNames = []): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $ProjectUser = $this->projectRepo->getProjectsJson($user->id) ?? [];

        $ProjectName = collect($ProjectUser)->pluck('name')->toArray();


        if (empty($requestProjectNames)) {
            return $ProjectName;
        }

        return array_values(array_intersect($requestProjectNames, $ProjectName));
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