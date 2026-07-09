<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\ProjectInterface;
use Illuminate\Support\Facades\DB;

class ProjectRepository implements ProjectInterface
{

    public function upsertProjects(int $userId, array $projectsArray): void
    {
        DB::table('jira_projects')->upsert($projectsArray, ['key'], ['name']);

        $projectKeys = array_column($projectsArray, 'key');

        $projectIds = DB::table('jira_projects')
            ->whereIn('key', $projectKeys)
            ->pluck('id')
            ->toArray();

        $user = User::find($userId);
        if ($user && !empty($projectIds)) {
            $user->jira_projects_json = json_encode($projectIds);
            $user->save();
        }
    }

    public function getProjectsJson(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $userProjects = parse_json_to_array($user->jira_projects_json);
        $roleProjects = parse_json_to_array($user->jira_projects_role_json);

        $mergedProjectIds = array_unique(array_merge($userProjects, $roleProjects));

        if (empty($mergedProjectIds)) {
            return [];
        }

        return DB::table('jira_projects')
            ->select('key', 'name')
            ->whereIn('id', $mergedProjectIds)
            ->get()
            ->toArray();
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