<?php

namespace App\Models;

use App\Enums\IsAdminEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['jira_username', 'jira_password', 'jira_projects_json', 'jira_display_name', 'jira_email', 'super_admin', 'is_admin', 'role_id'])]
#[Hidden(['jira_password'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $casts = [
        'jira_projects_json' => 'array',
        'super_admin' => IsAdminEnum::class,
        'is_admin' => IsAdminEnum::class,
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === IsAdminEnum::YES;
    }

    public function isSuperAdmin(): bool
    {
        return $this->super_admin === IsAdminEnum::YES;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if (!$this->role) {
            return false;
        }
        $permissions = $this->role->permission_json ?? [];
        return in_array($permission, $permissions);
    }

    public function getAuthPassword()
    {
        return $this->jira_password;
    }
}