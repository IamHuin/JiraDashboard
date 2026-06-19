<?php

namespace App\Repositories\Interfaces;

interface ProjectInterface
{
    public function updateProjectsJson(int $userId, array $projectsArray): void;

    public function getProjectsJson(int $userId): array;
}