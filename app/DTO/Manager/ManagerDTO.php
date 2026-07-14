<?php

namespace App\DTO\Manager;

use Spatie\DataTransferObject\DataTransferObject;

class ManagerDTO extends DataTransferObject
{
    public ?bool $super_admin;
    public ?string $user_name;
    public ?int $role_id;

    public static function fromArray(array $data): self
    {
        return new self([
            'super_admin' => isset($data['super_admin']) ? (bool)$data['super_admin'] : null,
            'user_name' => $data['user_name'] ?? null,
            'role_id' => $data['role_id'] ?? null,
        ]);
    }

    public function toArray(): array
    {
        return [
            'super_admin' => $this->super_admin,
            'user_name' => $this->user_name,
            'role_id' => $this->role_id,
        ];
    }
}