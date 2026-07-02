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
                'message' => 'Full sync completed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Full sync failed',
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
                'message' => 'Sync from lastSyncTime completed successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync from lastSyncTime failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}