<?php

namespace App\DTO\Project;

use Spatie\DataTransferObject\DataTransferObject;

class ProjectDTO extends DataTransferObject
{
    public string $key;
    public string $name;

    public static function fromArray(array $data): self
    {
        return new self([
            'key' => $data['key'] ?? '',
            'name' => $data['name'] ?? '',
        ]);
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
        ];
    }
}