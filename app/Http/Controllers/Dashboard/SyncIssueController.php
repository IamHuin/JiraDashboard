<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
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

    public function syncFullIssues(): JsonResponse
    {
        return $this->dispatchSync('full');
    }

    public function syncFromLastIssues(): JsonResponse
    {
        return $this->dispatchSync('last');
    }

    protected function dispatchSync(string $mode): JsonResponse
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

        Cache::put($key, SyncStatus::IDLE->value, now()->addHours(2)); // đặt trạng thái tạm trước khi job set RUNNING
        SyncIssueJob::dispatch(Auth::id(), $mode);

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