<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface USBudgetInterface
{
    public function getSubtaskKeys(?string $period, ?array $projectNames = []): array;

    public function upsertUSBudget(array $multipleData): void;

    public function getUSBudget(string $period, ?string $username, ?array $projectNames = [], ?int $perPage = null): LengthAwarePaginator;
}