<?php

namespace App\DTO\Manager;

use Spatie\DataTransferObject\DataTransferObject;

class ManagerDTO extends DataTransferObject
{
    public ?string $email;
    public ?bool $is_admin;
    public ?string $user_name;

    public static function fromArray(array $data): self
    {
        return new self([
            'email' => $data['email'] ?? null,
            'is_admin' => isset($data['is_admin']) ? (bool)$data['is_admin'] : null,
            'user_name' => $data['user_name'] ?? null,
        ]);
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'user_name' => $this->user_name,
        ];
    }
}