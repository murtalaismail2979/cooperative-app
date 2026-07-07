<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_rate', 5, 2)->default(20.00);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('monthly_payment', 12, 2);
            $table->integer('duration_months')->default(12);
            $table->integer('remaining_months')->default(12);
            $table->date('date_granted');
            $table->enum('status', ['active', 'fully_paid', 'defaulted'])->default('active');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};