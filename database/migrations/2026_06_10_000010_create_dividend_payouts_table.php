<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dividend_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dividend_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('units')->comment('Members active slots multiplied by months saved');
            $table->decimal('amount', 14, 2);
            $table->boolean('paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dividend_payouts');
    }
};