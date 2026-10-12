<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\MonthlySaving;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Since YLDA started savings in October 2021, remove all savings records from Jan to Sep 2021 for all members.
     */
    public function up(): void
    {
        DB::table('monthly_savings')
            ->where(function ($query) {
                $query->whereBetween('month', ['2021-01-01', '2021-09-30'])
                      ->orWhere(function ($q) {
                          $q->whereYear('month', 2021)
                            ->whereMonth('month', '<', 10);
                      });
            })
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Deletion cannot be automatically reversed without backup snapshot
    }
};
