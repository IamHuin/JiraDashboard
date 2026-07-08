<?php

namespace App\Services\Dashboard;

use App\Enums\PaginateEnum;
use App\Repositories\Interfaces\DashboardInterface;
use App\Repositories\Interfaces\MilestoneInterface;
use App\Repositories\Interfaces\ProjectInterface;
use App\Repositories\Interfaces\USBudgetInterface;
use App\Services\Cache\DashboardCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected DashboardInterface $dashboardRepo;
    protected ProjectInterface $projectRepo;
    public DashboardCacheService $cacheService;
    protected PaginationService $paginationService;
    protected USBudgetInterface $usbudgetRepo;
    protected MilestoneInterface $milestoneRepo;

    public function __construct(
        DashboardInterface    $dashboardRepo,
        ProjectInterface      $projectRepo,
        DashboardCacheService $cacheService,
        PaginationService  $paginationService,
        USBudgetInterface  $usbudgetRepo,
        MilestoneInterface $milestoneRepo
    )
    {
        $this->dashboardRepo = $dashboardRepo;
        $this->projectRepo = $projectRepo;
        $this->cacheService = $cacheService;
        $this->paginationService = $paginationService;
        $this->usbudgetRepo = $usbudgetRepo;
        $this->milestoneRepo = $milestoneRepo;
    }

    public function getOverview(string $period, ?array $projectNames = []): array
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

    public function getBugRatioLeaderboard(string $period, ?string $userName, ?array $projectNames = []): array
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

                $data = [
                    'details' => $this->paginationService->format($paginator)
                ];

                return json_decode(json_encode($data), true);
            }

            return ['details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getSlsxUlnlRatioLeaderboard(string $period, ?string $userName, ?array $projectNames = []): array
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

                $data = [
                    'details' => $this->paginationService->format($paginator)
                ];

                return json_decode(json_encode($data), true);
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

        $cacheKey = "user_{$user->id}_{$user->isAdmin()}_projects_list";
        $projects = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);
            if ($user->isAdmin()) {
                return $this->projectRepo->getAllProjects();
            } else {
                $data = $this->projectRepo->getProjectsJson($user->id);
                return json_decode(json_encode($data), true);
            }
        });

        return ['success' => true, 'data' => $projects ?? []];
    }

    public function getOverdueIssues(int $table_id,string $period, ?string $userName, ?array $projectNames = [], ?string $issuetype = null, ?string $status = null): array
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
        $cacheKey = "user_{$user->id}_overdues_{$table_id}_{$period}_{$userName}_{$issuetype}_{$status}_{$projectHash}_p{$page}_s{$perPage}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $issuetype, $status, $perPage, $user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            if ($period) {
                $paginator = $this->dashboardRepo->getOverdueIssues($period, $allowedProjectNames, $userName, $issuetype, $status, $perPage);

                $data = [
                    'details' => $this->paginationService->format($paginator)
                ];

                return json_decode(json_encode($data), true);
            }

            return ['details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getOverdueLogWork(int $table_id,string $period, ?string $userName, ?array $projectNames = [], ?string $issuetype = null, ?string $statusLogWork = null): array
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
        $cacheKey = "user_{$user->id}_overdues_{$table_id}_{$period}_{$userName}_{$issuetype}_{$statusLogWork}_{$projectHash}_p{$page}_s{$perPage}";

        $issues = Cache::remember($cacheKey, $this->cacheService->getTtl(), function () use ($period, $allowedProjectNames, $userName, $issuetype, $statusLogWork, $perPage, $user, $cacheKey) {
            $this->cacheService->trackKey($user->id, $cacheKey);

            if ($period) {
                $paginator = $this->dashboardRepo->getOverdueLogWork($period, $allowedProjectNames, $userName, $issuetype, $statusLogWork, $perPage);

                $data = [
                    'details' => $this->paginationService->format($paginator)
                ];

                return json_decode(json_encode($data), true);
            }

            return ['details' => ['list' => [], 'meta' => []]];
        });

        return format_dashboard_success($userName, $allowedProjectNames, $period, $issues);
    }

    public function getUSBudgets(string $period, ?string $username, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);
        if (empty($allowedProjectNames)) {
            return ['success' => true, 'data' => []];
        }

        if ($period) {
            $this->processUSBudget($period, $allowedProjectNames);

            $paginator = $this->usbudgetRepo->getUSBudget($period, $username, $allowedProjectNames, $perPage);
            $issues = [
                'details' => $this->paginationService->format($paginator)
            ];
        } else {
            $issues = ['details' => ['list' => [], 'meta' => []]];
        }

        return format_dashboard_success($username, $allowedProjectNames, $period, $issues);
    }

    public function getMilestones(string $period, ?string $report_type, ?array $projectNames = []): array
    {
        $user = auth()->user();
        if (!$user) return ['success' => false, 'data' => []];

        $perPage = (int)request('per_page', PaginateEnum::DEFAULT_PER_PAGE);

        $allowedProjectNames = $this->filterAllowedProjects($projectNames);
        if (empty($allowedProjectNames)) {
            return ['success' => true, 'data' => []];
        }

        if ($period) {
            $this->processUSBudget($period, $allowedProjectNames);

            $paginator = $this->milestoneRepo->getMilestones($period, $report_type, $allowedProjectNames, $perPage);
            $issues = [
                'details' => $this->paginationService->format($paginator)
            ];
        } else {
            $issues = ['details' => ['list' => [], 'meta' => []]];
        }

        return format_dashboard_success($user->jira_display_name, $allowedProjectNames, $period, $issues);
    }

    public function processUSBudget(string $period, ?array $projectNames = []): void
    {

        $keyStory = $this->usbudgetRepo->getSubtaskKeys($period, $projectNames);

        $USBudget = [];
        if (empty($keyStory)) {
            return;
        }

        $allStoryKeys = array_keys($keyStory);
        $allSubtaskKeys = collect($keyStory)->flatten()->unique()->toArray();
        $allKeys = array_merge($allStoryKeys, $allSubtaskKeys);

        $slsxData = DB::table('jira_issues')
            ->whereIn('key', $allKeys)
            ->select('key', 'slsx', 'summary', 'issuetype', 'status', 'assignee')
            ->get()
            ->keyBy('key')
            ->toArray();

        foreach ($keyStory as $storyKey => $subtaskKeys) {
            $slsxStory = (float)($slsxData[$storyKey]->slsx ?? 0);

            $sumSLSXSubTask = 0;

            foreach ($subtaskKeys as $subtaskKey) {
                $sumSLSXSubTask += (float)($slsxData[$subtaskKey]->slsx ?? 0);
            }

            $ratioSLSX = round($sumSLSXSubTask - $slsxStory, 3);

            if ($sumSLSXSubTask > $slsxStory) {
                $USBudget[] = [
                    'period' => $period,
                    'project_name' => json_encode($projectNames),
                    'key' => $storyKey,
                    'summary' => $slsxData[$storyKey]->summary ?? '',
                    'issuetype' => $slsxData[$storyKey]->issuetype ?? '',
                    'status' => $slsxData[$storyKey]->status,
                    'assignee' => $slsxData[$storyKey]->assignee ?? '',
                    'slsx' => $slsxStory,
                    'sumSLSXSubTask' => round($sumSLSXSubTask, 3),
                    'ratioSLSX' => $ratioSLSX
                ];
            }
        }

        if (!empty($USBudget)) {
            $this->usbudgetRepo->upsertUSBudget($USBudget);
        }
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

        $ProjectUser = $user->isAdmin() ? $this->projectRepo->getAllProjects() ?? [] : $this->projectRepo->getProjectsJson($user->id) ?? [];

        $ProjectName = collect($ProjectUser)->pluck('name')->toArray();

        if (empty($requestProjectNames)) return $ProjectName;

        return array_values(array_intersect($requestProjectNames, $ProjectName));
    }
}