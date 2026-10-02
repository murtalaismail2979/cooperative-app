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

    public function test_admin_can_record_negative_return_as_loss(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $investment = Investment::create([
            'name' => 'Poultry Business',
            'type' => 'agriculture',
            'capital_amount' => 50000.00,
            'total_returns' => 10000.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.investments.show', $investment))
            ->post(route('admin.investments.return', $investment), [
                'amount' => -2500.00,
                'return_date' => '2026-07-01',
                'description' => 'Loss due to mortality'
            ]);

        $response->assertRedirect(route('admin.investments.show', $investment));
        $this->assertDatabaseHas('investment_returns', [
            'investment_id' => $investment->id,
            'amount' => -2500.00,
            'description' => 'Loss due to mortality'
        ]);

        // Total returns should drop from 10000 to 7500
        $this->assertEquals(7500.00, $investment->fresh()->total_returns);
    }

    public function test_admin_can_create_and_update_investment_with_quantity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create buying and selling goods investment with quantity
        $response = $this->actingAs($admin)
            ->post(route('admin.investments.store'), [
                'name' => 'Maize Trading',
                'type' => 'buying_selling_goods',
                'capital_amount' => 15000.00,
                'quantity' => 120.50,
                'start_date' => '2026-01-01',
                'description' => 'Maize bulk purchase'
            ]);

        $response->assertRedirect(route('admin.investments.index'));
        $this->assertDatabaseHas('investments', [
            'name' => 'Maize Trading',
            'type' => 'buying_selling_goods',
            'quantity' => 120.50
        ]);

        $investment = Investment::where('name', 'Maize Trading')->first();

        // Update investment quantity
        $updateResponse = $this->actingAs($admin)
            ->put(route('admin.investments.update', $investment), [
                'name' => 'Maize Trading Updated',
                'type' => 'agriculture',
                'capital_amount' => 18000.00,
                'quantity' => 150.00,
                'start_date' => '2026-01-01',
                'status' => 'active',
                'description' => 'Updated maize bulk purchase'
            ]);

        $updateResponse->assertRedirect(route('admin.investments.show', $investment));
        $this->assertDatabaseHas('investments', [
            'id' => $investment->id,
            'name' => 'Maize Trading Updated',
            'type' => 'agriculture',
            'quantity' => 150.00
        ]);
    }

    public function test_admin_can_filter_investments_by_year_and_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $inv2025June = Investment::create([
            'name' => '2025 June Project',
            'type' => 'buying_selling_goods',
            'capital_amount' => 10000.00,
            'start_date' => '2025-06-15',
            'status' => 'active',
            'created_by' => $admin->id
        ]);

        $inv2026June = Investment::create([
            'name' => '2026 June Project',
            'type' => 'agriculture',
            'capital_amount' => 20000.00,
            'start_date' => '2026-06-20',
            'status' => 'active',
            'created_by' => $admin->id
        ]);

        $inv2026December = Investment::create([
            'name' => '2026 December Project',
            'type' => 'financing',
            'capital_amount' => 30000.00,
            'start_date' => '2026-12-10',
            'status' => 'active',
            'created_by' => $admin->id
        ]);

        // Filter by Year 2026
        $responseYear = $this->actingAs($admin)
            ->get(route('admin.investments.index', ['year' => 2026]));
        $responseYear->assertStatus(200);
        $responseYear->assertSee('2026 June Project');
        $responseYear->assertSee('2026 December Project');
        $responseYear->assertDontSee('2025 June Project');

        // Filter by Year 2026 and Month 6 (June)
        $responseMonth = $this->actingAs($admin)
            ->get(route('admin.investments.index', ['year' => 2026, 'month' => 6]));
        $responseMonth->assertStatus(200);
        $responseMonth->assertSee('2026 June Project');
        $responseMonth->assertDontSee('2026 December Project');
        $responseMonth->assertDontSee('2025 June Project');

        // Filter by Investment Type (financing)
        $responseType = $this->actingAs($admin)
            ->get(route('admin.investments.index', ['type' => 'financing']));
        $responseType->assertStatus(200);
        $responseType->assertSee('2026 December Project');
        $responseType->assertDontSee('2026 June Project');
        $responseType->assertDontSee('2025 June Project');
    }
}
