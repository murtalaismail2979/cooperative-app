<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('import_type'); // members, savings, running_charges, loans
            $table->string('original_filename');
            $table->string('duplicate_mode')->default('skip'); // skip, update, new_only
            $table->integer('total_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->string('status')->default('completed'); // completed, failed
            $table->longText('error_details')->nullable(); // JSON array of row errors
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_imports');
    }
};
