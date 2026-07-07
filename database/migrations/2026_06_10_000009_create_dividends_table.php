<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dividends', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->decimal('total_dividend_amount', 14, 2)->comment('Total pool to distribute');
            $table->integer('total_units')->comment('Total number of 2000 units across all members');
            $table->decimal('unit_value', 14, 2)->comment('total_dividend_amount / total_units');
            $table->timestamp('distributed_at')->nullable();
            $table->foreignId('distributed_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dividends');
    }
};