<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('registration_year');
            $table->index('role');
            $table->index('is_active');
        });

        Schema::table('monthly_savings', function (Blueprint $table) {
            $table->index('month');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->index('status');
            $table->index('date_granted');
            $table->index(['user_id', 'status']);
        });

        Schema::table('running_charges', function (Blueprint $table) {
            $table->index('month');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['registration_year']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('monthly_savings', function (Blueprint $table) {
            $table->dropIndex(['month']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['date_granted']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('running_charges', function (Blueprint $table) {
            $table->dropIndex(['month']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['status']);
        });
    }
};
