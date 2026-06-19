<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\ProjectInterface;

class ProjectRepository implements ProjectInterface
{

    public function updateProjectsJson(int $userId, array $projectsArray): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->jira_projects_json = $projectsArray;
            $user->save();
        }
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
}