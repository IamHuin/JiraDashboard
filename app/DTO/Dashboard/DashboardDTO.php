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
    public ?string $statusLogWork;
    public int $table_id;
    public ?string $report_type;
    public ?string $ticket_code;

    public static function fromArray(array $data): DashboardDTO
    {
        return new self(
            period: $data['period'] ?? Carbon::now()->format('m-Y'),
            user_name: $data['user_name'] ?? null,
            project_names: $data['project_names'] ?? [],
            issuetype: $data['issuetype'] ?? null,
            status: $data['status'] ?? null,
            statusLogWork: $data['statusLogWork'] ?? null,
            table_id: $data['table_id'] ?? 0,
            report_type: $data['report_type'] ?? null,
            ticket_code: $data['ticket_code'] ?? null,
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
            'statusLogWork' => $this->statusLogWork,
            'table_id' => $this->table_id,
            'report_type' => $this->report_type,
            'ticket_code' => $this->ticket_code,
        ];
    }
}