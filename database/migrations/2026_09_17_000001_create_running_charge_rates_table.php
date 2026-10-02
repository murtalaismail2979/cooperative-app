<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('running_charge_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('start_year');
            $table->integer('end_year');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        // Insert initial default rate intervals
        DB::table('running_charge_rates')->insert([
            [
                'start_year' => 2000,
                'end_year' => 2021,
                'amount' => 100.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'start_year' => 2022,
                'end_year' => 2023,
                'amount' => 300.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'start_year' => 2024,
                'end_year' => 2099,
                'amount' => 500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('running_charge_rates');
    }
};
