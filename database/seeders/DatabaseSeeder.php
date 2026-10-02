<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SlotSeeder::class,
        ]);

        \App\Models\InvestmentType::updateOrCreate(['slug' => 'buying_selling_goods'], ['name' => 'Buying & Selling Goods']);
        \App\Models\InvestmentType::updateOrCreate(['slug' => 'agriculture'], ['name' => 'Agriculture']);
        \App\Models\InvestmentType::updateOrCreate(['slug' => 'financing'], ['name' => 'Financing']);
    }
}