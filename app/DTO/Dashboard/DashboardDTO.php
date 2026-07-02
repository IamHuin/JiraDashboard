<?php

namespace App\DTO\Dashboard;

use Carbon\Carbon;
use Spatie\DataTransferObject\DataTransferObject;

class DashboardDTO extends DataTransferObject
{
    public string $period;
    public ?string $user_name;
    public ?array $project_names;
    public ?string $issuetype;
    public ?string $status;

    public static function fromArray(array $data): DashboardDTO
    {
        return new self(
            period: $data['period'] ?? Carbon::now()->format('m-Y'),
            user_name: $data['user_name'] ?? null,
            project_names: $data['project_names'] ?? [],
            issuetype: $data['issuetype'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'user_name' => $this->user_name,
            'project_names' => $this->project_names,
            'issuetype' => $this->issuetype,
            'status' => $this->status,
        ];
    }
}