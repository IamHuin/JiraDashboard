<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\USBudgetInterface;
use Carbon\Carbon;
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
}