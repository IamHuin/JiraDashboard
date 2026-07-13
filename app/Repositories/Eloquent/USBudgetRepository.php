<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\USBudgetInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class USBudgetRepository implements USBudgetInterface
{

    public function getSubtaskKeys(?string $period, ?array $projectNames = []): array
    {
        $query = DB::table('jira_issues')
            ->where('issuetype', 'Story')
            ->whereNotNull('subtask_keys');

        if ($period) {
            $date = Carbon::createFromFormat('m-Y', $period);
            $query->whereYear('created_at_jira', $date->year)
                ->whereMonth('created_at_jira', $date->month);
        }

        if (!empty($projectNames)) {
            $query->whereIn('project_name', $projectNames);
        }

        $stories = $query->select('key', 'subtask_keys')->get();
        $result = [];

        foreach ($stories as $story) {
            $subtaskKeys = json_decode($story->subtask_keys, true);

            $result[$story->key] = is_array($subtaskKeys) ? $subtaskKeys : [];
        }

        return $result;
    }

    public function upsertUSBudget(array $multipleData): void
    {
        if (empty($multipleData)) {
            return;
        }

        DB::table('jira_usbudgets')
            ->upsert(
                $multipleData,
                ['key'],
                ['period', 'project_name', 'summary', 'issuetype', 'assignee', 'display_name', 'slsx', 'sumSLSXSubTask', 'ratioSLSX', 'status', 'updated_at']
            );
    }

    public function getUSBudget(string $period, ?string $username, ?array $projectNames = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = DB::table('jira_usbudgets')->select([
            'id', 'key', 'summary', 'issuetype', 'assignee', 'display_name', 'status', 'slsx', 'sumSLSXSubTask', 'ratioSLSX'
        ]);

        if ($period) {
            $query->where('period', $period);
        }

        if (!empty($projectNames)) {
            $query->where(function ($q) use ($projectNames) {
                $q->whereRaw("JSON_OVERLAPS(project_name, ?)", [json_encode($projectNames)]);
            });
        }

        if (!empty($userName)) {
            $query->where(function ($q) use ($userName) {
                $q->where('assignee', 'like', "%{$userName}%")
                    ->orWhere('display_name', 'like', "%{$userName}%");
            });
        }


        return $query->orderBy('ratioSLSX', 'desc')->paginate($perPage);
    }
}