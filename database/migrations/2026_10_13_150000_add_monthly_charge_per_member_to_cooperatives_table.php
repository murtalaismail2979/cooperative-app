<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            $table->decimal('monthly_charge_per_member', 10, 2)->default(0.00)->after('base_slot_amount');
            $table->text('billing_notes')->nullable()->after('monthly_charge_per_member');
        });
    }

    public function down(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            $table->dropColumn(['monthly_charge_per_member', 'billing_notes']);
        });
    }
};
