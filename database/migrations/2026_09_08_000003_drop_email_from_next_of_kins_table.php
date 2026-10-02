<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('next_of_kins', function (Blueprint $table) {
            if (Schema::hasColumn('next_of_kins', 'email')) {
                $table->dropColumn('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('next_of_kins', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });
    }
};
