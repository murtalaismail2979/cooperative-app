<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('readmitted_at')->nullable()->after('approved_at');
        });

        // Backfill readmitted_at for members with prior re-admission history
        $histories = DB::table('member_slot_histories')
            ->where('reason', 'like', 'Re-admitted%')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($histories as $h) {
            DB::table('users')
                ->where('id', $h->user_id)
                ->whereNull('readmitted_at')
                ->update(['readmitted_at' => Carbon::parse($h->created_at)->format('Y-m-d')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('readmitted_at');
        });
    }
};
