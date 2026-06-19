<?php

namespace App\Http\Controllers\Dashboard;

use App\DTO\Dashboard\DashboardDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\BugRatio\BugRatioRequest;
use App\Http\Requests\Overview\OverviewRequest;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function overview(OverviewRequest $request)
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $data = $this->dashboardService->getOverview(
            $dto->period_start,
            $dto->period_end,
            $dto->project_names
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function getBugRatio(BugRatioRequest $request)
    {
        $dto = DashboardDTO::fromArray($request->validated());
        $result = $this->dashboardService->getBugRatio(
            $dto->period_start,
            $dto->period_end,
            $dto->user_name,
            $dto->project_names
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
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
