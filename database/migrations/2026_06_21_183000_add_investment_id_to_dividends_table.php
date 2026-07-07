<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dividends', function (Blueprint $table) {
            $table->foreignId('investment_id')->nullable()->constrained('investments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('dividends', function (Blueprint $table) {
            $table->dropForeign(['investment_id']);
            $table->dropColumn('investment_id');
        });
    }
};
