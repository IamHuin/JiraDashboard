<?php

namespace Database\Seeders;

use App\Enums\IsAdminEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'jira_username' => 'admin',
            'jira_password' => Hash::make('admin'),
            'jira_display_name' => 'Super Admin',
            'super_admin' => IsAdminEnum::YES,
            'is_admin' => IsAdminEnum::NO,
        ]);
    }
}
