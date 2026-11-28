<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Admin', 'email' => 'admin@gym.test', 'role' => User::ROLE_ADMIN],
            ['name' => 'Staff', 'email' => 'staff@gym.test', 'role' => User::ROLE_STAFF],
            ['name' => 'Trainer', 'email' => 'trainer@gym.test', 'role' => User::ROLE_TRAINER],
            ['name' => 'Trainer 2', 'email' => 'trainer2@gym.test', 'role' => User::ROLE_TRAINER],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                $u + ['password' => Hash::make('password'), 'is_active' => true]
            );
        }
    }
}
