<?php

namespace App\DTO\Dashboard;

use Spatie\DataTransferObject\DataTransferObject;

class DashboardDTO extends DataTransferObject
{
    public ?string $period_start;
    public ?string $period_end;
    public ?string $user_name;
    public ?array $project_names;

    public static function fromArray(array $data): DashboardDTO
    {
        return new self(
            period_start: $data['period_start'] ?? null,
            period_end: $data['period_end'] ?? null,
            user_name: $data['user_name'] ?? null,
            project_names: $data['project_names'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'user_name' => $this->user_name,
            'project_names' => $this->project_names,
        ];
    }
}