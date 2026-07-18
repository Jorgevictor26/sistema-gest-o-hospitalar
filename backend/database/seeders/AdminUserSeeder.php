<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create or update the initial administrator account.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'jorgekvictor26@gmail.com'],
            [
                'name' => 'Jorge Victor',
                'password' => Hash::make('12345678'),
                'is_active' => true,
            ]
        );

        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
