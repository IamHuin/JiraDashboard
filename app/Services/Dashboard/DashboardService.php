<?php

namespace App\Services\Dashboard;

use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Services\Cache\DashboardCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected DashboardInterface $dashboardRepo;
    protected ProjectInterface $projectRepo;
    public DashboardCacheService $cacheService;

    public function __construct(
        DashboardInterface    $dashboardRepo,
        ProjectInterface      $projectRepo,
        DashboardCacheService $cacheService
    )
    {
        $this->dashboardRepo = $dashboardRepo;
        $this->projectRepo = $projectRepo;
        $this->cacheService = $cacheService;
    }

    public function getOverview(?string $periodStart, ?string $periodEnd, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $period = $this->normalizePeriod($periodStart, $periodEnd);
        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return [
                'success' => true,
                'data' => [],
            ];
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_overview_{$periodStart}_{$periodEnd}_{$projectHash}";

        $data = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($allowedProjectNames, $period, $user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            return $this->dashboardRepo->getOverview([
                'project_names' => $allowedProjectNames,
                'period_start'  => $period['start_date'],
                'period_end'    => $period['end_date'],
            ]);
        });

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public function getBugRatio(?string $periodStart, ?string $periodEnd, ?string $userName, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $period = $this->normalizePeriod($periodStart, $periodEnd);
        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return [
                'success' => true,
                'data' => [
                    'user' => $userName,
                    'project_names' => [],
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'start_date' => $period['start_date'],
                    'end_date' => $period['end_date'],
                    'issues' => [],
                ],
            ];
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_bug_ratio_{$periodStart}_{$periodEnd}_{$userName}_{$projectHash}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            return ($period['start_date'] && $period['end_date'])
                ? $this->dashboardRepo->getIssuesByPeriod($period['start_date'], $period['end_date'], $allowedProjectNames, $userName)
                : [];
        });

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

        $cacheKey = "user_{$user->id}_projects_list";
        $projects = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);
            return $this->projectRepo->getProjectsJson($user->id);
        });

        return [
            'success' => true,
            'data' => $projects ?? [],
        ];
    }

    public function getTrackedCacheKeys(): array
    {
        $user = auth()->user();
        if (!$user) return [];

        return $this->cacheService->getTrackedKeys($user->id);
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