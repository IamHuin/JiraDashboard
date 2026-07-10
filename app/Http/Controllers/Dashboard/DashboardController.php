<?php

namespace App\Http\Controllers\Dashboard;

use App\DTO\Dashboard\DashboardDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardRequest;
use App\Http\Requests\Milestone\MilestoneRequest;
use App\Http\Requests\NLTC\ImportRequest;
use App\Http\Requests\Overdue\OverdueRequest;
use App\Services\Dashboard\DashboardService;
use App\Services\JiraNltcService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected JiraNltcService $nltcService;

    public function __construct(DashboardService $dashboardService, JiraNltcService $nltcService)
    {
        $this->dashboardService = $dashboardService;
        $this->nltcService = $nltcService;
    }

    public function overview(DashboardRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getOverview(
            $dto->period,
            $dto->project_names
        );

        return response()->json($result);
    }

    public function getBugRatioLeaderboard(DashboardRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getBugRatioLeaderboard(
            $dto->period,
            $dto->user_name,
            $dto->project_names
        );

        return response()->json($result);
    }

    public function getSlsxUlnlRatioLeaderboard(DashboardRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getSlsxUlnlRatioLeaderboard(
            $dto->period,
            $dto->user_name,
            $dto->project_names
        );

        return response()->json($result);
    }

    public function getProjects(): JsonResponse
    {
        try {
            $result = $this->dashboardService->getProjects();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể lấy danh sách dự án.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data'    => $result['data']
            ], 200);

        } catch (\Exception $e) {
            Log::error("API Get Project Failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi hệ thống xảy ra, vui lòng thử lại sau.'
            ], 500);
        }
    }

    public function getOverdueIssues(OverdueRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getOverdueIssues(
            $dto->table_id,
            $dto->period,
            $dto->user_name,
            $dto->project_names,
            $dto->issuetype,
            $dto->status
        );

        return response()->json($result);
    }
    public function getOverdueLogWork(OverdueRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getOverdueLogWork(
            $dto->table_id,
            $dto->period,
            $dto->user_name,
            $dto->project_names,
            $dto->issuetype,
            $dto->statusLogWork,
        );

        return response()->json($result);
    }

    public function getUSBudgets(DashboardRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getUSBudgets(
            $dto->period,
            $dto->user_name,
            $dto->project_names
        );

        return response()->json($result);
    }
    public function getMilestones(MilestoneRequest $request): JsonResponse
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getMilestones(
            $dto->period,
            $dto->report_type,
            $dto->ticket_code,
            $dto->project_names,
        );

        return response()->json($result);
    }

    public function importSlsx(ImportRequest $request): JsonResponse
    {
        $result = $this->nltcService->importData($request->file('file'));
        return response()->json($result);
    }

    public function getTrackedCacheKeys(): JsonResponse
    {
        try {
            $cacheKeys = $this->dashboardService->getTrackedCacheKeys();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách cache key thành công.',
                'count'   => count($cacheKeys),
                'data'    => $cacheKeys
            ], 200);

        } catch (\Exception $e) {
            Log::error("API Get Tracked Cache Keys Failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi hệ thống xảy ra khi lấy danh sách cache.'
            ], 500);
        }
    }

    public function clearCache(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng chưa được xác thực.'
                ], 401);
            }

            $this->dashboardService->cacheService->clearUserCache($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa toàn bộ bộ nhớ đệm (Cache) thành công. Dữ liệu sẽ được làm mới ở lần tải trang tiếp theo.'
            ], 200);

        } catch (\Exception $e) {
            Log::error("API Clear Cache Failed for User " . (auth()->id() ?? 'unknown') . ": " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra khi xóa bộ nhớ đệm.'
            ], 500);
        }
    }
}