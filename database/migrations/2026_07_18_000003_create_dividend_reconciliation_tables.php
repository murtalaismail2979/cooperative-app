<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('financial_year_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->decimal('total_business_profit', 12, 2)->default(0.00);
            $table->decimal('total_financing_profit', 12, 2)->default(0.00);
            $table->decimal('total_recognized_loss', 12, 2)->default(0.00);
            $table->decimal('net_sharable_profit', 12, 2)->default(0.00);
            $table->decimal('cooperative_amount', 12, 2)->default(0.00);
            $table->decimal('management_amount', 12, 2)->default(0.00);
            $table->decimal('adjusted_member_pool', 12, 2)->default(0.00);
            $table->decimal('total_distributed', 12, 2)->default(0.00);
            $table->decimal('total_loss_adjustment', 12, 2)->default(0.00);
            $table->decimal('total_over_distributed', 12, 2)->default(0.00);
            $table->decimal('total_outstanding_payable', 12, 2)->default(0.00);
            $table->enum('status', ['draft', 'approved', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dividend_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('financial_year_reconciliations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('original_dividend_amount', 12, 2)->default(0.00);
            $table->decimal('loss_adjustment_amount', 12, 2)->default(0.00);
            $table->decimal('final_entitlement_amount', 12, 2)->default(0.00);
            $table->decimal('amount_already_paid', 12, 2)->default(0.00);
            $table->decimal('overpayment_amount', 12, 2)->default(0.00);
            $table->decimal('underpayment_amount', 12, 2)->default(0.00);
            $table->decimal('amount_recovered', 12, 2)->default(0.00);
            $table->enum('status', ['pending', 'partially_recovered', 'fully_recovered', 'settled'])->default('pending');
            $table->string('recovery_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dividend_recovery_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dividend_adjustment_id')->constrained('dividend_adjustments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('Cash');
            $table->string('reference_number')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('loss_carry_forwards', function (Blueprint $table) {
            $table->id();
            $table->integer('from_year');
            $table->integer('to_year');
            $table->decimal('unabsorbed_loss_amount', 12, 2);
            $table->decimal('absorbed_amount', 12, 2)->default(0.00);
            $table->enum('status', ['active', 'fully_absorbed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loss_carry_forwards');
        Schema::dropIfExists('dividend_recovery_payments');
        Schema::dropIfExists('dividend_adjustments');
        Schema::dropIfExists('financial_year_reconciliations');
        Schema::dropIfExists('financial_years');
    }
};
