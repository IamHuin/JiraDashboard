<?php

namespace App\Repositories\Interfaces;

interface USBudgetInterface
{
    public function getSubtaskKeys(?string $period, ?array $projectNames = []): array;
}