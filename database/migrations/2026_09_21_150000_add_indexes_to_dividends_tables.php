<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dividends', function (Blueprint $table) {
            $table->index(['investment_id', 'distributed_at']);
            $table->index('year');
            $table->index('created_at');
        });

        Schema::table('dividend_payouts', function (Blueprint $table) {
            $table->index(['dividend_id', 'user_id']);
            $table->index(['user_id', 'paid']);
            $table->index('paid_date');
        });
    }

    public function down(): void
    {
        Schema::table('dividends', function (Blueprint $table) {
            $table->dropIndex(['investment_id', 'distributed_at']);
            $table->dropIndex(['year']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('dividend_payouts', function (Blueprint $table) {
            $table->dropIndex(['dividend_id', 'user_id']);
            $table->dropIndex(['user_id', 'paid']);
            $table->dropIndex(['paid_date']);
        });
    }
};
