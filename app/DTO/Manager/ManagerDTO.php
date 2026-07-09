<?php

namespace App\DTO\Manager;

use Spatie\DataTransferObject\DataTransferObject;

class ManagerDTO extends DataTransferObject
{
    public ?string $user_name;
    public ?int $role_id;

    public static function fromArray(array $data): self
    {
        return new self([
            'user_name' => $data['user_name'] ?? null,
            'role_id' => $data['role_id'] ?? null,
        ]);
    }

    public function toArray(): array
    {
        return [
            'user_name' => $this->user_name,
            'role_id' => $this->role_id,
        ];
    }
}