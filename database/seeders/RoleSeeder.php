<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $managementUsers = [
            ['name' => 'Admin', 'email' => 'admin@ylda.com', 'role' => 'admin', 'phone' => '08000000000'],
            ['name' => 'Chairman', 'email' => 'chairman@ylda.com', 'role' => 'chairman', 'phone' => '08000000001'],
            ['name' => 'Secretary', 'email' => 'secretary@ylda.com', 'role' => 'secretary', 'phone' => '08000000002'],
            ['name' => 'Treasurer', 'email' => 'treasurer@ylda.com', 'role' => 'treasurer', 'phone' => '08000000003'],
        ];

        foreach ($managementUsers as $managementUser) {
            User::updateOrCreate(
                ['email' => $managementUser['email']],
                [
                    'name' => $managementUser['name'],
                    'password' => Hash::make('password'),
                    'member_code' => null,
                    'registration_year' => null,
                    'phone' => $managementUser['phone'],
                    'address' => 'YLDA Headquarters',
                    'role' => $managementUser['role'],
                    'is_active' => true,
                ]
            );
        }
    }
}