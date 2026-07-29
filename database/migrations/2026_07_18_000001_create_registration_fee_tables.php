<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2)->default(1000.00);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('registration_fee_histories', function (Blueprint $table) {
            $table->id();
            $table->decimal('old_amount', 12, 2);
            $table->decimal('new_amount', 12, 2);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('registration_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('fee_amount', 12, 2)->default(1000.00);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->enum('status', ['unpaid', 'partially_paid', 'fully_paid', 'requires_verification', 'cancelled'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('registration_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_fee_id')->constrained('registration_fees')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('Cash');
            $table->string('reference_number')->nullable();
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_fee_payments');
        Schema::dropIfExists('registration_fees');
        Schema::dropIfExists('registration_fee_histories');
        Schema::dropIfExists('registration_fee_settings');
    }
};
