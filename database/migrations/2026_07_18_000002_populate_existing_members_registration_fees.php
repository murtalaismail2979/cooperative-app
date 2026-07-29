<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;
use App\Models\RegistrationFee;
use App\Services\RegistrationFeeService;

return new class extends Migration
{
    public function up(): void
    {
        $service = new RegistrationFeeService();
        $feeAmount = $service->getCurrentFeeAmount();

        $members = User::where('role', 'member')->get();

        foreach ($members as $member) {
            RegistrationFee::firstOrCreate(
                ['user_id' => $member->id],
                [
                    'fee_amount' => $feeAmount,
                    'total_paid' => 0.00,
                    'status' => 'requires_verification',
                    'notes' => 'Existing member initialized with requires_verification status for administrative verification.',
                ]
            );
        }
    }

    public function down(): void
    {
        // Keep existing financial records safe; do not drop rows on down unless explicitly required
    }
};
