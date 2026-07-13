<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sync\SyncRequest;
use App\Jobs\SyncIssueJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SyncIssueController extends Controller
{
    protected function cacheKey(string $mode): string
    {
        return "jira-sync-status:" . Auth::id() . ":{$mode}";
    }

    public function syncFullIssues(SyncRequest $request): JsonResponse
    {
        return $this->dispatchSync('full', $request->input('project_names', []), $request->input('period_from'), $request->input('period_to'));
    }

    public function syncFromLastIssues(): JsonResponse
    {
        return $this->dispatchSync('last');
    }

    protected function dispatchSync(string $mode, array $project_names = [], string $period_from = '', string $period_to = ''): JsonResponse
    {
        $key = $this->cacheKey($mode);
        $current = Cache::get($key);

        if ($current === SyncStatus::RUNNING->value) {
            return response()->json([
                'success' => false,
                'status'  => $current,
                'message' => 'Đang đồng bộ, vui lòng đợi hoàn tất trước khi bấm lại',
            ], 429);
        }

        Cache::put($key, SyncStatus::RUNNING->value, now()->addHours(2)); // Đã sửa thành RUNNING để khóa request trùng
        SyncIssueJob::dispatch(Auth::id(), $mode, $project_names, $period_from, $period_to);

        return response()->json([
            'success' => true,
            'message' => 'Đã nhận yêu cầu đồng bộ, đang xử lý nền',
        ]);
    }

    public function status(string $mode): JsonResponse
    {
        $status = Cache::get($this->cacheKey($mode), SyncStatus::IDLE->value);

        return response()->json([
            'status' => $status,
        ]);
    }
}