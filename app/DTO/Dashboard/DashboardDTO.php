<?php

namespace App\DTO\Dashboard;

use Spatie\DataTransferObject\DataTransferObject;

class DashboardDTO extends DataTransferObject
{
    public ?string $period;
    public ?string $user_name;
    public ?array $project_names;

    public static function fromArray(array $data): DashboardDTO
    {
        return new self(
            period: $data['period'] ?? null,
            user_name: $data['user_name'] ?? null,
            project_names: $data['project_names'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'user_name' => $this->user_name,
            'project_names' => $this->project_names,
        ];
    }
}