<?php

namespace App\Enums;

enum PermissionEnum:string
{
    case IMPORT = 'import';
    case SYNC = 'sync';
    case DASHBOARD = 'dashboard';
    case MANAGE = 'manage';

    public static function getAll(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
