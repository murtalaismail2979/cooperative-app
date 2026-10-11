<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cooperatives')) {
            Schema::create('cooperatives', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('logo_path')->nullable();
                $table->string('primary_color')->default('#1e40af');
                $table->string('secondary_color')->default('#0f172a');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('enable_shares')->default(false);
                $table->boolean('enable_savings')->default(true);
                $table->decimal('base_slot_amount', 10, 2)->default(2000.00);
                $table->decimal('share_unit_price', 12, 2)->default(1000.00);
                $table->integer('min_share_units')->default(10);
                $table->string('dividend_allocation_basis', 50)->default('savings_only');
                $table->decimal('hybrid_shares_percentage', 5, 2)->default(50.00);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'cooperative_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('cooperative_id')->nullable()->after('id')->constrained('cooperatives')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'cooperative_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['cooperative_id']);
                $table->dropColumn('cooperative_id');
            });
        }
        Schema::dropIfExists('cooperatives');
    }
};
