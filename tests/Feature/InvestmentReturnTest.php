<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investment;
use App\Models\InvestmentReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_investment_return(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $investment = Investment::create([
            'name' => 'Gold Investment',
            'type' => 'buying_selling_goods',
            'capital_amount' => 5000.00,
            'total_returns' => 1500.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $return = InvestmentReturn::create([
            'investment_id' => $investment->id,
            'amount' => 1000.00,
            'return_date' => '2026-06-01',
            'description' => 'First return payout',
            'recorded_by' => $admin->id
        ]);

        // Verify pre-conditions
        $this->assertEquals(1500.00, $investment->fresh()->total_returns);
        $this->assertDatabaseHas('investment_returns', ['id' => $return->id]);

        // Execute delete request
        $response = $this->actingAs($admin)
            ->delete(route('admin.investments.destroy_return', [$investment, $return]));

        // Assert response and database state
        $response->assertRedirect(route('admin.investments.show', $investment));
        $this->assertDatabaseMissing('investment_returns', ['id' => $return->id]);
        
        // Assert total returns got decremented
        $this->assertEquals(500.00, $investment->fresh()->total_returns);
    }

    public function test_cannot_delete_return_for_closed_investment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $investment = Investment::create([
            'name' => 'Closed Real Estate',
            'type' => 'agriculture',
            'capital_amount' => 10000.00,
            'total_returns' => 2000.00,
            'status' => 'completed',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $return = InvestmentReturn::create([
            'investment_id' => $investment->id,
            'amount' => 2000.00,
            'return_date' => '2026-06-01',
            'description' => 'Final return payout',
            'recorded_by' => $admin->id
        ]);

        // Verify pre-conditions
        $this->assertEquals(2000.00, $investment->fresh()->total_returns);

        // Execute delete request
        $response = $this->actingAs($admin)
            ->delete(route('admin.investments.destroy_return', [$investment, $return]));

        // Assert database record was NOT deleted because status is completed (not active)
        $this->assertDatabaseHas('investment_returns', ['id' => $return->id]);
        $this->assertEquals(2000.00, $investment->fresh()->total_returns);
    }

    public function test_admin_can_create_investment_with_end_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.investments.store'), [
                'name' => 'Real Estate Portfolio',
                'type' => 'buying_selling_goods',
                'capital_amount' => 12000.00,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'description' => 'Real estate buy-sell operation'
            ]);

        $response->assertRedirect(route('admin.investments.index'));
        $this->assertDatabaseHas('investments', [
            'name' => 'Real Estate Portfolio',
            'end_date' => '2026-12-31 00:00:00'
        ]);
    }

    public function test_investment_creation_fails_if_end_date_before_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.investments.store'), [
                'name' => 'Real Estate Portfolio',
                'type' => 'buying_selling_goods',
                'capital_amount' => 12000.00,
                'start_date' => '2026-01-01',
                'end_date' => '2025-12-31',
                'description' => 'Invalid end date'
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    public function test_admin_can_create_investment_with_financing_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.investments.store'), [
                'name' => 'General Financing Scheme',
                'type' => 'financing',
                'capital_amount' => 25000.00,
                'start_date' => '2026-06-01',
                'description' => 'Financing type investment'
            ]);

        $response->assertRedirect(route('admin.investments.index'));
        $this->assertDatabaseHas('investments', [
            'name' => 'General Financing Scheme',
            'type' => 'financing',
            'capital_amount' => 25000.00
        ]);
    }
}
