<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@ylda.com',
            'password' => Hash::make('password'),
            'member_code' => User::generateMemberCode(),
            'registration_year' => date('Y'),
            'phone' => '08000000000',
            'address' => '123 Admin Office, YLDA Headquarters',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create Treasurer
        User::create([
            'name' => 'Treasurer',
            'email' => 'treasurer@ylda.com',
            'password' => Hash::make('password'),
            'member_code' => User::generateMemberCode(),
            'registration_year' => date('Y'),
            'phone' => '08000000001',
            'address' => '456 Treasurer Office, YLDA Building',
            'role' => 'treasurer',
            'is_active' => true,
        ]);

        // Create sample members
        for ($i = 1; $i <= 5; $i++) {
            $member = User::create([
                'name' => "Member {$i}",
                'email' => "member{$i}@ylda.com",
                'password' => Hash::make('password'),
                'member_code' => User::generateMemberCode(),
                'registration_year' => date('Y'),
                'phone' => "0800000000{$i}",
                'address' => "{$i}00 Member Street, Sample City",
                'role' => 'member',
                'is_active' => true,
            ]);

            // Each member gets 1-5 savings slots
            $slots = rand(1, 5);
            for ($slot = 1; $slot <= $slots; $slot++) {
                $member->savingsSlots()->create([
                    'slot_number' => $slot,
                    'is_active' => true,
                ]);
            }

            // Create Next of Kin
            $relationships = ['Spouse', 'Sibling', 'Child', 'Parent', 'Friend'];
            $member->nextOfKin()->create([
                'name' => "Kin for Member {$i}",
                'phone' => "0801111111{$i}",
                'relationship' => $relationships[array_rand($relationships)],
                'email' => "kin{$i}@example.com",
                'address' => "{$i}01 Kin Street, Kin City",
            ]);
        }
    }
}