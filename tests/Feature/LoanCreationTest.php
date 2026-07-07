<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_loan_with_default_parameters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($admin)
            ->post(route('admin.loans.store'), [
                'user_id' => $member->id,
                'principal_amount' => 10000.00,
                'date_granted' => '2026-06-01',
            ]);

        $response->assertRedirect(route('admin.loans.index'));

        // Assert default: 20% interest, 12 months duration
        $this->assertDatabaseHas('loans', [
            'user_id' => $member->id,
            'principal_amount' => 10000.00,
            'profit_rate' => 20.00,
            'total_amount' => 12000.00,
            'monthly_payment' => 1000.00,
            'duration_months' => 12,
            'remaining_months' => 12,
            'date_granted' => '2026-06-01 00:00:00',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_loan_with_custom_parameters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($admin)
            ->post(route('admin.loans.store'), [
                'user_id' => $member->id,
                'principal_amount' => 50000.00,
                'profit_rate' => 15.5,
                'duration_months' => 6,
                'date_granted' => '2026-06-01',
            ]);

        $response->assertRedirect(route('admin.loans.index'));

        // 50000 * 1.155 = 57750 total, 57750 / 6 = 9625 monthly payment
        $this->assertDatabaseHas('loans', [
            'user_id' => $member->id,
            'principal_amount' => 50000.00,
            'profit_rate' => 15.50,
            'total_amount' => 57750.00,
            'monthly_payment' => 9625.00,
            'duration_months' => 6,
            'remaining_months' => 6,
            'date_granted' => '2026-06-01 00:00:00',
            'status' => 'active',
        ]);
    }

    public function test_loan_creation_validation_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // Test negative profit rate
        $response = $this->actingAs($admin)
            ->post(route('admin.loans.store'), [
                'user_id' => $member->id,
                'principal_amount' => 10000.00,
                'profit_rate' => -5,
                'duration_months' => 12,
                'date_granted' => '2026-06-01',
            ]);

        $response->assertSessionHasErrors(['profit_rate']);

        // Test non-integer duration
        $response2 = $this->actingAs($admin)
            ->post(route('admin.loans.store'), [
                'user_id' => $member->id,
                'principal_amount' => 10000.00,
                'profit_rate' => 20,
                'duration_months' => 12.5,
                'date_granted' => '2026-06-01',
            ]);

        $response2->assertSessionHasErrors(['duration_months']);
    }
}
