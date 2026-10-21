<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cooperative_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount_requested', 12, 2);
            $table->integer('duration_months')->default(12);
            $table->text('purpose')->nullable();
            $table->decimal('member_total_savings_at_request', 12, 2)->default(0);
            $table->decimal('max_eligible_amount_at_request', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->integer('approved_duration_months')->nullable();
            $table->decimal('approved_profit_rate', 5, 2)->nullable();
            $table->date('date_granted')->nullable();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['cooperative_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_requests');
    }
};
