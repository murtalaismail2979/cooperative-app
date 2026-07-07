<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_code')->nullable()->unique()->after('id');
            $table->year('registration_year')->nullable()->after('member_code');
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', ['admin', 'treasurer', 'member'])->default('member')->after('phone');
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['member_code', 'registration_year', 'phone', 'role', 'is_active']);
        });
    }
};