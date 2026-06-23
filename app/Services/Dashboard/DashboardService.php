<?php

namespace App\Services\Dashboard;

use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Services\Cache\DashboardCacheService;
use App\Enums\PaginateEnum;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected DashboardInterface $dashboardRepo;
    protected ProjectInterface $projectRepo;
    public DashboardCacheService $cacheService;
    protected PaginationService $paginationService;

    public function __construct(
        DashboardInterface    $dashboardRepo,
        ProjectInterface      $projectRepo,
        DashboardCacheService $cacheService,
        PaginationService     $paginationService
    )
    {
        $this->dashboardRepo = $dashboardRepo;
        $this->projectRepo = $projectRepo;
        $this->cacheService = $cacheService;
        $this->paginationService = $paginationService;
    }

    public function getOverview(?string $period, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return [
                'success' => true,
                'data' => [],
            ];
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_overview_{$period}_{$projectHash}";

        $data = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($allowedProjectNames, $period, $user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            return $this->dashboardRepo->getOverview([
                'project_names' => $allowedProjectNames,
                'period' => $period,
            ]);
        });

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public function getBugRatioMyself(?string $period, ?string $userName, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $page = (int)request('page', PaginateEnum::DEFAULT_PAGE);
        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return format_dashboard_empty($userName, $period, $page, $perPage);
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_bug_ratio_myself_{$period}_{$userName}_{$projectHash}_p{$page}_s{$perPage}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $user, $perPage, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            if ($period) {
                $ratio = $this->dashboardRepo->getBugRatioByPeriod($period, $allowedProjectNames, $userName);
                $paginator = $this->dashboardRepo->getListDetail($period, $userName, 'Bug', $allowedProjectNames, $perPage);

                return [
                    'ratio' => $ratio,
                    'details' => $this->paginationService->format($paginator)
                ];
            }

            return ['ratio' => [], 'details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getBugRatioLeaderboard(?string $period, ?string $userName, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $page = (int)request('page', PaginateEnum::DEFAULT_PAGE);
        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return format_dashboard_empty($userName, $period, $page, $perPage);
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_bug_ratio_leaderboard_{$period}_{$userName}_{$projectHash}_p{$page}_s{$perPage}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $user, $perPage, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            if ($period) {
                $paginator = $this->dashboardRepo->getBugRatioByPeriod($period, $allowedProjectNames, $userName, $perPage);

                return [
                    'details' => $this->paginationService->format($paginator)
                ];
            }

            return ['details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getSlsxUlnlRatioMyself(?string $period, ?string $userName, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $page = (int)request('page', PaginateEnum::DEFAULT_PAGE);
        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return format_dashboard_empty($userName, $period, $page, $perPage);
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_slsx_ulnl_ratio_myself_{$period}_{$userName}_{$projectHash}_p{$page}_s{$perPage}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $user, $perPage, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            if ($period) {
                $ratio = $this->dashboardRepo->getSlsxUlnlRatioByPeriod($period, $allowedProjectNames, $userName);
                $paginator = $this->dashboardRepo->getListDetail($period, $userName, 'Sub-task', $allowedProjectNames, $perPage);

                return [
                    'ratio' => $ratio,
                    'details' => $this->paginationService->format($paginator)
                ];
            }

            return ['ratio' => [], 'details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getSlsxUlnlRatioLeaderboard(?string $period, ?string $userName, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $page = (int)request('page', PaginateEnum::DEFAULT_PAGE);
        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);

        if (empty($allowedProjectNames)) {
            return format_dashboard_empty($userName, $period, $page, $perPage);
        }

        $projectHash = md5(json_encode($allowedProjectNames));
        $cacheKey = "user_{$user->id}_slsx_ulnl_ratio_leaderboard_{$period}_{$userName}_{$projectHash}_p{$page}_s{$perPage}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $user, $perPage, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            if ($period) {
                $paginator = $this->dashboardRepo->getSlsxUlnlRatioByPeriod($period, $allowedProjectNames, $userName, $perPage);

                return [
                    'details' => $this->paginationService->format($paginator)
                ];
            }

            return ['details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getProjects(): array
    {
        $user = auth()->user();
        if (!$user) {
            return ['success' => false, 'message' => 'User not authenticated', 'data' => []];
        }

        $cacheKey = "user_{$user->id}_projects_list";
        $projects = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);
            return $this->projectRepo->getProjectsJson($user->id);
        });

        return ['success' => true, 'data' => $projects ?? []];
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
        if (!$user) return [];

        $ProjectUser = $this->projectRepo->getProjectsJson($user->id) ?? [];
        $ProjectName = collect($ProjectUser)->pluck('name')->toArray();

        if (empty($requestProjectNames)) return $ProjectName;

        return array_values(array_intersect($requestProjectNames, $ProjectName));
    }
}