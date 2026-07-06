<?php

namespace App\Repositories\Interfaces;

interface ProjectInterface
{
    public function upsertProjects(int $userId, array $projectsArray): void;
    public function getProjectsJson(int $userId): array;

    public function getAllProjects(): array;
}