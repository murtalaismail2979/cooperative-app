<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RegistrationFee;
use App\Services\RegistrationFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExistingMemberReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_member_can_be_reconciled_by_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingMember = User::factory()->create(['role' => 'member']);
        
        // Initial state for existing member: requires_verification
        $service = new RegistrationFeeService();
        $fee = $service->createObligationForMember($existingMember, 10000.00, 'requires_verification');

        $this->assertEquals('requires_verification', $fee->status);

        $response = $this->actingAs($admin)->post(route('admin.registration-fees.reconcile', $existingMember->id), [
            'status' => 'fully_paid',
            'fee_amount' => 10000.00,
            'total_paid' => 10000.00,
            'notes' => 'Verified historical ledger record from 2024 paper archive.',
        ]);

        $response->assertSessionHasNoErrors();
        $fee->refresh();

        $this->assertEquals('fully_paid', $fee->status);
        $this->assertEquals(10000.00, $fee->total_paid);
        $this->assertStringContainsString('2024 paper archive', $fee->notes);
    }
}
