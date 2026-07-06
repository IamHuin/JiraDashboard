<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncIssueService;
use Exception;
use Illuminate\Http\JsonResponse;

class SyncIssueController extends Controller
{
    protected $jiraSync;

    public function __construct(SyncIssueService $jiraSync)
    {
        $this->jiraSync = $jiraSync;
    }

    public function syncFullIssues(): JsonResponse
    {
        try {
            $this->jiraSync->syncFullIssues();
            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ toàn bộ dữ liệu thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ toàn bộ dữ liệu thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function syncFromLastIssues(): JsonResponse
    {
        try {
            $this->jiraSync->syncFromLastIssues();
            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ dữ liệu thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đồng bộ từ thời điểm cuối cùng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
