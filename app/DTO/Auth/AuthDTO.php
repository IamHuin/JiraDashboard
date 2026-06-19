<?php

namespace App\DTO\Auth;

use Spatie\DataTransferObject\DataTransferObject;

class AuthDTO extends DataTransferObject
{
    public string $jira_username;
    public string $jira_password;

    public static function login(string $jira_username, string $jira_password): AuthDTO
    {
        return new self(jira_username: $jira_username, jira_password: $jira_password);
    }

    public static function fromArray(array $data): AuthDTO
    {
        return new self(
            jira_username: $data['jira_username'],
            jira_password: $data['jira_password']
        );
    }

    public function toArray(): array
    {
        return [
            'jira_username' => $this->jira_username,
            'jira_password' => $this->jira_password,
        ];
    }
}