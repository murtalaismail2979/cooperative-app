<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_savings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('savings_slot_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->default(2000.00);
            $table->date('month')->comment('First day of month (e.g., 2026-01-01)');
            $table->date('payment_date')->nullable();
            $table->enum('status', ['paid', 'pending'])->default('pending');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'savings_slot_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_savings');
    }
};