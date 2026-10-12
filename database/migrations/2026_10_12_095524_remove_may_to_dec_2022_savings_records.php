<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * For the year 2022, members only contributed for Jan to Apr 2022.
     * Remove all savings records for May to Dec 2022 for all members.
     */
    public function up(): void
    {
        DB::table('monthly_savings')
            ->where(function ($query) {
                $query->whereBetween('month', ['2022-05-01', '2022-12-31'])
                      ->orWhere(function ($q) {
                          $q->whereYear('month', 2022)
                            ->whereMonth('month', '>=', 5);
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
