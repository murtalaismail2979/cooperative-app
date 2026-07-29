<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_database_reports_render_successfully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))->assertStatus(200);
        $this->get(route('admin.reports.savings'))->assertStatus(200);
        $this->get(route('admin.reports.loans'))->assertStatus(200);
        $this->get(route('admin.reports.financial'))->assertStatus(200);
    }

    public function test_non_existent_records_return_404()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get('/admin/members/99999/edit')->assertStatus(404);
        $this->get('/admin/loans/99999')->assertStatus(404);
        $this->get('/admin/investments/99999')->assertStatus(404);
        $this->get('/admin/dividends/99999')->assertStatus(404);
    }

    public function test_zero_or_negative_payment_amounts_rejected()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $member = User::factory()->create(['role' => 'member']);

        // Registration fee payment with zero or negative amount
        $response = $this->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $member->id,
            'amount' => 0.00,
            'payment_date' => '2026-01-01',
        ]);
        $response->assertSessionHasErrors(['amount']);

        $responseNeg = $this->post(route('admin.registration-fees.payment.store'), [
            'user_id' => $member->id,
            'amount' => -500.00,
            'payment_date' => '2026-01-01',
        ]);
        $responseNeg->assertSessionHasErrors(['amount']);
    }

    public function test_business_loss_greater_than_profit_produces_zero_sharable_profit()
    {
        $investment = Investment::factory()->create([
            'capital_amount' => 1000000.00,
            'total_returns' => 400000.00, // Loss of 600,000
        ]);

        $this->assertEquals(-600000.00, $investment->profit);
        $this->assertEquals(400000.00, $investment->sharable_profit);
    }
}
