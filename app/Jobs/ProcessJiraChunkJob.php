<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Sync\IssueTransformerService;
use App\Repositories\Interfaces\SyncIssueInterface;
use App\Repositories\Interfaces\IssueOverdueInterface;
use App\Services\Ping\ConnectJiraService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Log;

class ProcessJiraChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    protected ?array $rawIssues;
    protected ?string $url;
    protected ?User $user;

    public function __construct(?array $rawIssues = null, ?string $url = null, ?User $user = null)
    {
        $this->rawIssues = $rawIssues;
        $this->url = $url;
        $this->user = $user;
    }

    public function handle(
        SyncIssueInterface $syncRepo,
        IssueOverdueInterface $issueOverdueRepo,
        IssueTransformerService $transformer
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            if ($this->url && $this->user) {
                $jiraService = new class extends ConnectJiraService {
                    public function fetch($u, $url) { return $this->connectToJira($u, $url); }
                };
                $clonedUser = clone $this->user;
                $clonedUser->jira_password = Crypt::decryptString($clonedUser->jira_password);
                $data = $jiraService->fetch($clonedUser, $this->url);
                $issues = $data['issues'] ?? [];
                unset($data, $clonedUser);
            } else {
                $issues = $this->rawIssues ?? [];
            }

            if (empty($issues)) {
                return;
            }

            // 1. Transform và lưu trữ Issue chính
            $transformedIssues = $transformer->transformMany($issues);
            $syncRepo->saveIssues($transformedIssues);

            // 2. Xử lý Overdue trực tiếp cho chunk hiện tại (Map chuẩn theo Schema DB của bạn)
            $this->processOverdue($issues, $issueOverdueRepo, $transformer);

            // 3. Cập nhật mốc thời gian lớn nhất
            $maxCreated = collect($issues)->map(fn($i) => $i['fields']['created'] ?? null)->filter()->max();
            if ($maxCreated) {
                $syncRepo->updateSyncTime(Carbon::parse($maxCreated)->format('Y-m-d H:i:s'));
            }

            unset($transformedIssues, $issues);
            gc_collect_cycles();

        } catch (Exception $e) {
            Log::error("Lỗi xử lý Chunk tại Job: " . $e->getMessage());
            throw $e;
        }
    }

    protected function processOverdue(array $issues, $issueOverdueRepo, $transformer): void
    {
        $targetTypes = ['Sub-task', 'Story', 'Milestone'];
        $bulkOverdueData = [];

        foreach ($issues as $issueData) {
            $issueType = $issueData['fields']['issuetype']['name'] ?? null;
            if (!in_array($issueType, $targetTypes)) continue;

            try {
                $detailData = $transformer->transformSingle($issueData);

                $createdAtJira = $issueData['fields']['created'] ?? null;
                $period = $createdAtJira ? Carbon::parse($createdAtJira)->format('m-Y') : Carbon::now()->format('m-Y');

                $bulkOverdueData[] = [
                    'key'          => $issueData['key'] ?? null,
                    'period'       => $period,
                    'project_name' => $issueData['fields']['project']['name'] ?? $detailData['project'] ?? null,
                    'summary'      => $issueData['fields']['summary'] ?? null,
                    'issuetype'    => $issueType,
                    'assignee'     => $detailData['assignee'] ?? null,
                    'enddate'      => $detailData['enddate'] ?? null,
                    'status'       => $detailData['status'] ?? null,
                    'statusText'   => $detailData['statusText'] ?? null,
                    'statusLogWork'=> $detailData['statusLogWork'] ?? null,
                    'statusTextLogWork' => $detailData['statusTextLogWork'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            } catch (Exception $e) {}
        }

        if (!empty($bulkOverdueData)) {
            $issueOverdueRepo->upsertIssues($bulkOverdueData);
        }
    }
}