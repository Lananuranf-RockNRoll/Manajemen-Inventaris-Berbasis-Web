<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Administrator',
                'email'    => 'admin@inventory.test',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Manager Gudang',
                'email'    => 'manager@inventory.test',
                'password' => Hash::make('password'),
                'role'     => 'manager',
            ],
            [
                'name'     => 'Staff Gudang',
                'email'    => 'staff@inventory.test',
                'password' => Hash::make('password'),
                'role'     => 'staff',
            ],
            [
                'name'     => 'Viewer',
                'email'    => 'viewer@inventory.test',
                'password' => Hash::make('password'),
                'role'     => 'viewer',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
