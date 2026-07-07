<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investment;
use App\Models\Expense;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use App\Models\Dividend;
use App\Models\DividendPayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyDividendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_distribute_dividends_monthly_based_on_investment_sharable_profit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        // 1. Create three members
        $userA = User::factory()->create(['role' => 'member', 'name' => 'User Alice', 'is_active' => true]);
        $slotA = SavingsSlot::create(['user_id' => $userA->id, 'slot_number' => 1, 'is_active' => true]);

        $userB = User::factory()->create(['role' => 'member', 'name' => 'User Bob', 'is_active' => true]);
        $slotB = SavingsSlot::create(['user_id' => $userB->id, 'slot_number' => 1, 'is_active' => true]);

        $userC = User::factory()->create(['role' => 'member', 'name' => 'User Charlie', 'is_active' => true]);
        $slotC = SavingsSlot::create(['user_id' => $userC->id, 'slot_number' => 1, 'is_active' => true]);

        // 2. Record paid savings for User A (Alice has savings in June)
        MonthlySaving::create([
            'user_id' => $userA->id,
            'savings_slot_id' => $slotA->id,
            'amount' => 2000.00,
            'month' => '2026-04-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);
        MonthlySaving::create([
            'user_id' => $userA->id,
            'savings_slot_id' => $slotA->id,
            'amount' => 2000.00,
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);
        MonthlySaving::create([
            'user_id' => $userA->id,
            'savings_slot_id' => $slotA->id,
            'amount' => 2000.00,
            'month' => '2026-06-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);

        // 3. Record paid savings for User B (Bob also has savings in June)
        MonthlySaving::create([
            'user_id' => $userB->id,
            'savings_slot_id' => $slotB->id,
            'amount' => 2000.00,
            'month' => '2026-04-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);
        MonthlySaving::create([
            'user_id' => $userB->id,
            'savings_slot_id' => $slotB->id,
            'amount' => 2000.00,
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);
        MonthlySaving::create([
            'user_id' => $userB->id,
            'savings_slot_id' => $slotB->id,
            'amount' => 2000.00,
            'month' => '2026-06-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);

        // Record paid savings for User C (Charlie does NOT have savings in June, only April & May)
        MonthlySaving::create([
            'user_id' => $userC->id,
            'savings_slot_id' => $slotC->id,
            'amount' => 2000.00,
            'month' => '2026-04-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);
        MonthlySaving::create([
            'user_id' => $userC->id,
            'savings_slot_id' => $slotC->id,
            'amount' => 2000.00,
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $treasurer->id
        ]);

        // 4. Create an investment starting in June 2026 (Month 6)
        $investment = Investment::create([
            'name' => 'Cocoa Business',
            'type' => 'buying_selling_goods',
            'capital_amount' => 10000.00,
            'total_returns' => 0.00,
            'status' => 'active',
            'start_date' => '2026-06-15',
            'created_by' => $admin->id
        ]);

        // Record a return of 5000.00
        $this->actingAs($admin)
            ->post(route('admin.investments.return', $investment), [
                'amount' => 5000.00,
                'return_date' => '2026-06-20',
                'description' => 'Return from Cocoa sales'
            ]);

        // Record and approve an expense of 2000.00
        $expense = Expense::create([
            'category' => 'business',
            'description' => 'Bagging costs',
            'amount' => 2000.00,
            'expense_date' => '2026-06-18',
            'status' => 'pending',
            'requested_by' => $treasurer->id,
            'investment_id' => $investment->id
        ]);
        $this->actingAs($admin)
            ->post(route('admin.expenses.approve', $expense));

        $investment = $investment->fresh();
        $this->assertEquals(3000.00, $investment->sharable_profit);

        // 5. Test create page displays this investment
        $responseCreate = $this->actingAs($admin)
            ->get(route('admin.dividends.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Cocoa Business');
        $responseCreate->assertSee('₦3,000.00');

        // 6. Distribute dividends for this investment (total units = 2, unit value = 1500.00)
        $responseStore = $this->actingAs($admin)
            ->post(route('admin.dividends.store'), [
                'investment_id' => $investment->id,
                'total_dividend_amount' => 3000.00
            ]);

        $dividend = Dividend::where('investment_id', $investment->id)->first();
        $this->assertNotNull($dividend);
        $responseStore->assertRedirect(route('admin.dividends.show', $dividend));

        $this->assertEquals(8, $dividend->total_units);
        $this->assertEquals(337.50, $dividend->unit_value);

        // Verify payout records
        $payoutA = DividendPayout::where('dividend_id', $dividend->id)->where('user_id', $userA->id)->first();
        $this->assertNotNull($payoutA);
        $this->assertEquals(3, $payoutA->units);
        $this->assertEquals(1012.50, $payoutA->amount);

        $payoutB = DividendPayout::where('dividend_id', $dividend->id)->where('user_id', $userB->id)->first();
        $this->assertNotNull($payoutB);
        $this->assertEquals(3, $payoutB->units);
        $this->assertEquals(1012.50, $payoutB->amount);

        // Verify User C is NOT excluded anymore since they have cumulative savings
        $payoutC = DividendPayout::where('dividend_id', $dividend->id)->where('user_id', $userC->id)->first();
        $this->assertNotNull($payoutC);
        $this->assertEquals(2, $payoutC->units);
        $this->assertEquals(675.00, $payoutC->amount);

        // 7. Verify index view displays details
        $responseIndex = $this->actingAs($admin)
            ->get(route('admin.dividends.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Cocoa Business');
        $responseIndex->assertSee('₦2,700.00');
        $responseIndex->assertSee('₦337.50');
        $responseIndex->assertSee('Total Shared: ₦2,700.00');
        $responseIndex->assertSee('Total Paid: ₦0.00');
        $responseIndex->assertSee('Total Pending: ₦2,700.00');

        // Verify member cumulative dividends summary is displayed for active savers
        $responseIndex->assertSee('User Alice');
        $responseIndex->assertSee('User Bob');

        // 8. Verify show view displays details
        $responseShow = $this->actingAs($admin)
            ->get(route('admin.dividends.show', $dividend));
        $responseShow->assertOk();
        $responseShow->assertSee('Cocoa Business');
        $responseShow->assertSee('Unit Value:</strong> ₦337.50', false);

        // 9. Verify Member A and Member B views display details
        $responseMemberA = $this->actingAs($userA)
            ->get(route('member.dividends'));
        $responseMemberA->assertOk();
        $responseMemberA->assertSee('Cocoa Business');
        $responseMemberA->assertSee('₦1,012.50');
        $responseMemberA->assertSee('3'); // units

        $responseMemberB = $this->actingAs($userB)
            ->get(route('member.dividends'));
        $responseMemberB->assertOk();
        $responseMemberB->assertSee('Cocoa Business');
        $responseMemberB->assertSee('₦1,012.50');
        $responseMemberB->assertSee('3'); // units
    }

    public function test_dividend_distribution_fails_if_no_savings_at_business_start(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create an investment with no savings records before its start date
        $investment = Investment::create([
            'name' => 'Empty Business',
            'type' => 'agriculture',
            'capital_amount' => 5000.00,
            'total_returns' => 1000.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.dividends.store'), [
                'investment_id' => $investment->id,
                'total_dividend_amount' => 1000.00
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_admin_can_delete_dividend_distribution(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member', 'is_active' => true]);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000.00,
            'month' => '2026-05-01',
            'status' => 'paid',
            'recorded_by' => $admin->id
        ]);

        $investment = Investment::create([
            'name' => 'Cocoa Business',
            'type' => 'buying_selling_goods',
            'capital_amount' => 10000.00,
            'total_returns' => 5000.00,
            'status' => 'active',
            'start_date' => '2026-05-15',
            'created_by' => $admin->id
        ]);

        // Distribute dividends
        $dividend = Dividend::create([
            'year' => 2026,
            'investment_id' => $investment->id,
            'total_dividend_amount' => 5000.00,
            'total_units' => 1,
            'unit_value' => 5000.00,
            'distributed_at' => now(),
            'distributed_by' => $admin->id,
        ]);

        $payout = $dividend->payouts()->create([
            'user_id' => $member->id,
            'units' => 1,
            'amount' => 5000.00,
            'paid' => false,
        ]);

        $this->assertDatabaseHas('dividends', ['id' => $dividend->id]);
        $this->assertDatabaseHas('dividend_payouts', ['id' => $payout->id]);

        // Delete dividend
        $response = $this->actingAs($admin)
            ->delete(route('admin.dividends.destroy', $dividend));

        $response->assertRedirect(route('admin.dividends.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('dividends', ['id' => $dividend->id]);
        $this->assertDatabaseMissing('dividend_payouts', ['id' => $payout->id]);

        // Confirm investment is available again
        $availableInvestments = (new \App\Services\DividendService())->getAvailableInvestments();
        $this->assertTrue($availableInvestments->contains($investment));
    }
}
