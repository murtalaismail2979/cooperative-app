<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            if (!Schema::hasColumn('cooperatives', 'state')) {
                $table->string('state', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('cooperatives', 'lga')) {
                $table->string('lga', 100)->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            if (Schema::hasColumn('cooperatives', 'lga')) {
                $table->dropColumn('lga');
            }
            if (Schema::hasColumn('cooperatives', 'state')) {
                $table->dropColumn('state');
            }
        });
    }
};
