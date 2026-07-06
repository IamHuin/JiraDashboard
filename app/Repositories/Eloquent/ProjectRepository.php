<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\ProjectInterface;
use Illuminate\Support\Facades\DB;

class ProjectRepository implements ProjectInterface
{

    public function upsertProjects(int $userId, array $projectsArray): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->jira_projects_json = $projectsArray;
            $user->save();
        }
        DB::table('jira_projects')->upsert($projectsArray, ['key'], ['name']);
    }

    public function getProjectsJson(int $userId): array
    {
        $user = User::find($userId);

        if (!$user || !$user->jira_projects_json) {
            return [];
        }

        if (is_string($user->jira_projects_json)) {
            return json_decode($user->jira_projects_json, true) ?? [];
        }

        return $user->jira_projects_json;
    }

    public function getAllProjects(): array
    {
        return DB::table('jira_projects')
            ->select('key', 'name')
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }
}