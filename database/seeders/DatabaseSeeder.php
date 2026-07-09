<?php

namespace Database\Seeders;

use App\Enums\IsAdminEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'Super Admin'],
            ['jira_projects_json' => json_encode([])]
        );

        Role::updateOrCreate(
            ['name' => 'User'],
            ['jira_projects_json' => json_encode([])]
        );

        $role_id = Role::where('name', 'Super Admin')->value('id');

        User::updateOrCreate(
            ['jira_username' => 'admin'],
            [
                'jira_password'      => Hash::make('admin'),
                'jira_display_name'  => 'Super Admin',
                'super_admin'        => IsAdminEnum::YES,
//                'role_id'            => $role_id,
            ]
        );
    }
}