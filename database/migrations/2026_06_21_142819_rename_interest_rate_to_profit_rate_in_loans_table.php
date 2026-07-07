<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'sqlite') {
            Schema::table('loans', function (Blueprint $table) {
                $table->renameColumn('interest_rate', 'profit_rate');
            });
        } else {
            DB::statement('ALTER TABLE loans CHANGE interest_rate profit_rate DECIMAL(5,2) DEFAULT 20.00');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            Schema::table('loans', function (Blueprint $table) {
                $table->renameColumn('profit_rate', 'interest_rate');
            });
        } else {
            DB::statement('ALTER TABLE loans CHANGE profit_rate interest_rate DECIMAL(5,2) DEFAULT 20.00');
        }
    }
};
