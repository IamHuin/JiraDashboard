<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'jira_projects_json'])]
class Role extends Model
{
    protected $table = 'roles';

    protected $casts = [
        'jira_projects_json' => 'array',
    ];
}
