<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dividends', function (Blueprint $table) {
            $table->decimal('original_sharable_profit', 14, 2)->nullable()->after('investment_id')->comment('Original profit before deductions');
            $table->decimal('cooperative_amount', 14, 2)->nullable()->after('original_sharable_profit')->comment('5% allocated to cooperative');
            $table->decimal('management_amount', 14, 2)->nullable()->after('cooperative_amount')->comment('5% allocated to management');
            $table->decimal('member_distribution_pool', 14, 2)->nullable()->after('management_amount')->comment('90% distributed to members');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dividends', function (Blueprint $table) {
            $table->dropColumn([
                'original_sharable_profit',
                'cooperative_amount',
                'management_amount',
                'member_distribution_pool',
            ]);
        });
    }
};
