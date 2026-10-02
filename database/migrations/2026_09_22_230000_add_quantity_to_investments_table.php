<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('investments', 'quantity')) {
            Schema::table('investments', function (Blueprint $table) {
                $table->decimal('quantity', 12, 2)->nullable()->after('capital_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('investments', 'quantity')) {
            Schema::table('investments', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
