<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investment;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_treasurer_can_submit_expense_linked_to_active_investment(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $investment = Investment::create([
            'name' => 'Farming Project',
            'type' => 'agriculture',
            'capital_amount' => 5000.00,
            'total_returns' => 0.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.expenses.store'), [
                'category' => 'operational',
                'description' => 'Farming seeds expense',
                'amount' => 150.00,
                'expense_date' => '2026-06-01',
                'investment_id' => $investment->id
            ]);

        $response->assertRedirect(route('treasurer.expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'description' => 'Farming seeds expense',
            'investment_id' => $investment->id,
            'status' => 'pending'
        ]);
    }

    public function test_expense_submission_fails_with_completed_investment(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $investment = Investment::create([
            'name' => 'Farming Project',
            'type' => 'agriculture',
            'capital_amount' => 5000.00,
            'total_returns' => 0.00,
            'status' => 'completed', // completed status is not active
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.expenses.store'), [
                'category' => 'operational',
                'description' => 'Farming seeds expense',
                'amount' => 150.00,
                'expense_date' => '2026-06-01',
                'investment_id' => $investment->id
            ]);

        $response->assertSessionHasErrors('investment_id');
    }

    public function test_admin_can_view_investment_relationship_and_approve_expense(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $investment = Investment::create([
            'name' => 'Import Goods business',
            'type' => 'buying_selling_goods',
            'capital_amount' => 8000.00,
            'total_returns' => 0.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $expense = Expense::create([
            'category' => 'business',
            'description' => 'Buying containers',
            'amount' => 2000.00,
            'expense_date' => '2026-06-02',
            'status' => 'pending',
            'requested_by' => $treasurer->id,
            'investment_id' => $investment->id
        ]);

        // Check index list
        $responseIndex = $this->actingAs($admin)
            ->get(route('admin.expenses.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Import Goods business');

        // Check pending list
        $responsePending = $this->actingAs($admin)
            ->get(route('admin.expenses.pending'));
        $responsePending->assertOk();
        $responsePending->assertSee('Import Goods business');

        // Approve expense
        $responseApprove = $this->actingAs($admin)
            ->post(route('admin.expenses.approve', $expense));
        
        $responseApprove->assertRedirect();
        $this->assertEquals('approved', $expense->fresh()->status);

        // Check investment details show view has the approved expense
        $responseShow = $this->actingAs($admin)
            ->get(route('admin.investments.show', $investment));
        
        $responseShow->assertOk();
        $responseShow->assertSee('Buying containers');
        $responseShow->assertSee('₦2,000.00');
    }

    public function test_admin_can_record_expense_directly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $investment = Investment::create([
            'name' => 'General business',
            'type' => 'buying_selling_goods',
            'capital_amount' => 5000.00,
            'total_returns' => 0.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.expenses.store'), [
                'category' => 'business',
                'description' => 'Admin recorded expense',
                'amount' => 500.00,
                'expense_date' => '2026-06-03',
                'investment_id' => $investment->id
            ]);

        $response->assertRedirect(route('admin.expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'description' => 'Admin recorded expense',
            'investment_id' => $investment->id,
            'status' => 'approved',
            'approved_by' => $admin->id
        ]);
    }

    public function test_expenses_are_deducted_from_returns_to_give_sharable_profit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        // 1. Create an investment with capital and record a return
        $investment = Investment::create([
            'name' => 'Tech Hardware Business',
            'type' => 'buying_selling_goods',
            'capital_amount' => 1000.00,
            'total_returns' => 0.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        // Record a return of 1500.00
        $this->actingAs($admin)
            ->post(route('admin.investments.return', $investment), [
                'amount' => 1500.00,
                'return_date' => '2026-02-01',
                'description' => 'First batch sales'
            ]);

        $investment = $investment->fresh();
        $this->assertEquals(1500.00, $investment->total_returns);
        $this->assertEquals(1500.00, $investment->net_returns);
        $this->assertEquals(500.00, $investment->profit); // 1500 - 1000 = 500
        $this->assertEquals(150.00, $investment->roi); // (1500 / 1000) * 100 = 150%

        // 2. Submit an expense of 200.00 linked to this investment
        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.expenses.store'), [
                'category' => 'operational',
                'description' => 'Delivery cost',
                'amount' => 200.00,
                'expense_date' => '2026-02-05',
                'investment_id' => $investment->id
            ]);

        $expense = Expense::where('description', 'Delivery cost')->first();

        // The expense is pending, so it should not be deducted from returns yet
        $investment = $investment->fresh();
        $this->assertEquals(1500.00, $investment->net_returns);
        $this->assertEquals(500.00, $investment->profit);
        $this->assertEquals(150.00, $investment->roi);

        // 3. Admin approves the expense
        $this->actingAs($admin)
            ->post(route('admin.expenses.approve', $expense));

        // Now the expense is approved. Let's verify the recalculated numbers.
        $investment = $investment->fresh();
        $this->assertEquals(1500.00, $investment->total_returns);
        $this->assertEquals(200.00, $investment->approved_expenses);
        $this->assertEquals(1300.00, $investment->net_returns);
        $this->assertEquals(300.00, $investment->profit);
        $this->assertEquals(130.00, $investment->roi);

        // Check index list sees both total_returns and sharable_profit
        $responseIndex = $this->actingAs($admin)
            ->get(route('admin.investments.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('₦1,500.00');
        $responseIndex->assertSee('₦1,300.00');

        // Check show details sees all calculated fields
        $responseShow = $this->actingAs($admin)
            ->get(route('admin.investments.show', $investment));
        $responseShow->assertOk();
        $responseShow->assertSee('Returns:</strong> ₦1,500.00', false);
        $responseShow->assertSee('Expenses Incurred:</strong> ₦200.00', false);
        $responseShow->assertSee('Sharable Profit:</strong> <span class="fw-bold text-success">₦1,300.00', false);
        $responseShow->assertSee('130%', false);
    }
}

