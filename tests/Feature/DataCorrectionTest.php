<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\RunningCharge;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Investment;
use App\Models\InvestmentReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $treasurer;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->treasurer = User::create([
            'name' => 'Treasurer User',
            'email' => 'treasurer@test.com',
            'password' => bcrypt('password'),
            'role' => 'treasurer',
        ]);

        $this->member = User::create([
            'name' => 'Member User',
            'email' => 'member@test.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        // Create savings slots for the member
        for ($i = 1; $i <= 3; $i++) {
            SavingsSlot::create([
                'user_id' => $this->member->id,
                'slot_number' => $i,
                'is_active' => true,
            ]);
        }
    }

    /** @test */
    public function admin_can_edit_and_delete_savings()
    {
        $this->actingAs($this->admin);

        // Record savings first
        $month = '2026-06-01';
        $slots = $this->member->savingsSlots->pluck('id')->toArray();
        
        $this->post(route('admin.savings.store'), [
            'member_id' => $this->member->id,
            'month' => $month,
            'slots' => $slots,
            'payment_date' => '2026-06-01'
        ])->assertRedirect(route('admin.running-charges.index'));

        $this->assertDatabaseCount('monthly_savings', 3);
        $this->assertDatabaseCount('running_charges', 1);

        // Edit/Update Savings to only pay 2 slots
        $newMonth = '2026-07-01';
        $newSlots = [$slots[0], $slots[1]];

        $this->put(route('admin.savings.update'), [
            'member_id' => $this->member->id,
            'old_month' => $month,
            'month' => $newMonth,
            'slots' => $newSlots,
            'payment_date' => '2026-07-05',
        ])->assertRedirect(route('admin.savings.history'));

        // Old records deleted, new ones created
        $this->assertDatabaseCount('monthly_savings', 2);
        $this->assertDatabaseCount('running_charges', 1);
        $this->assertDatabaseHas('monthly_savings', [
            'user_id' => $this->member->id,
            'month' => '2026-07-01 00:00:00',
            'payment_date' => '2026-07-05 00:00:00',
        ]);
        $this->assertDatabaseHas('running_charges', [
            'user_id' => $this->member->id,
            'month' => '2026-07-01 00:00:00',
        ]);

        // Delete Savings
        $this->delete(route('admin.savings.destroy'), [
            'user_id' => $this->member->id,
            'month' => '2026-07-01',
        ])->assertRedirect(route('admin.savings.history'));

        $this->assertDatabaseCount('monthly_savings', 0);
        $this->assertDatabaseCount('running_charges', 0);
    }

    /** @test */
    public function treasurer_can_edit_and_delete_savings()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->treasurer);

        $month = '2026-06-01';
        $slots = $this->member->savingsSlots->pluck('id')->toArray();
        
        $this->post(route('treasurer.savings.store'), [
            'user_id' => $this->member->id,
            'month' => $month,
            'slot_ids' => $slots,
            'payment_date' => '2026-06-01'
        ]);

        $this->assertDatabaseCount('monthly_savings', 3);

        // Edit
        $this->put(route('treasurer.savings.update'), [
            'member_id' => $this->member->id,
            'old_month' => $month,
            'month' => '2026-06-01',
            'slots' => [$slots[0]],
            'payment_date' => '2026-06-10',
        ])->assertRedirect(route('treasurer.savings.history'));

        $this->assertDatabaseCount('monthly_savings', 1);

        // Delete
        $this->delete(route('treasurer.savings.destroy'), [
            'user_id' => $this->member->id,
            'month' => '2026-06-01',
        ])->assertRedirect(route('treasurer.savings.history'));

        $this->assertDatabaseCount('monthly_savings', 0);
    }

    /** @test */
    public function admin_and_treasurer_can_edit_and_delete_running_charges()
    {
        $charge = RunningCharge::create([
            'user_id' => $this->member->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'payment_date' => '2026-06-01',
            'status' => 'paid',
            'recorded_by' => $this->admin->id,
        ]);

        // Admin Edit
        $this->actingAs($this->admin);
        $this->put(route('admin.running-charges.update', $charge), [
            'amount' => 600,
            'month' => '2026-07-01',
            'payment_date' => '2026-07-02',
        ])->assertRedirect(route('admin.running-charges.history'));

        $this->assertDatabaseHas('running_charges', [
            'id' => $charge->id,
            'amount' => 600,
            'month' => '2026-07-01 00:00:00',
        ]);

        // Treasurer Edit
        $this->actingAs($this->treasurer);
        $this->put(route('treasurer.running-charges.update', $charge), [
            'amount' => 450,
            'month' => '2026-08-01',
            'payment_date' => '2026-08-02',
        ])->assertRedirect(route('treasurer.running-charges.history'));

        $this->assertDatabaseHas('running_charges', [
            'id' => $charge->id,
            'amount' => 450,
            'month' => '2026-08-01 00:00:00',
        ]);

        // Delete
        $this->delete(route('treasurer.running-charges.destroy', $charge))
            ->assertRedirect(route('treasurer.running-charges.history'));

        $this->assertDatabaseCount('running_charges', 0);
    }

    /** @test */
    public function admin_and_treasurer_can_edit_and_delete_loans_and_repayments()
    {
        // 1. Create a loan
        $this->actingAs($this->admin);
        $loanResponse = $this->post(route('admin.loans.store'), [
            'user_id' => $this->member->id,
            'principal_amount' => 100000,
            'profit_rate' => 20,
            'duration_months' => 10,
            'date_granted' => '2026-06-01',
        ])->assertRedirect(route('admin.loans.index'));

        $loan = Loan::first();
        $this->assertEquals(120000, $loan->total_amount);
        $this->assertEquals(12000, $loan->monthly_payment);

        // 2. Add repayment
        $this->post(route('admin.loans.repayment', $loan), [
            'amount' => 12000,
            'payment_date' => '2026-06-15',
        ]);

        $this->assertEquals(9, $loan->fresh()->remaining_months);
        $this->assertEquals(108000, $loan->fresh()->outstanding_balance);

        // 3. Edit Loan details
        $this->put(route('admin.loans.update', $loan), [
            'principal_amount' => 80000,
            'profit_rate' => 10,
            'duration_months' => 8,
            'date_granted' => '2026-06-01',
            'status' => 'active',
        ])->assertRedirect(route('admin.loans.show', $loan));

        // Recalculations verified: Total = 88000. Monthly = 11000.
        // Remaining months = 8 duration - 1 repayment = 7 remaining.
        $loan = $loan->fresh();
        $this->assertEquals(88000, $loan->total_amount);
        $this->assertEquals(11000, $loan->monthly_payment);
        $this->assertEquals(7, $loan->remaining_months);
        $this->assertEquals(76000, $loan->outstanding_balance); // 88000 - 12000 repayment

        // 4. Edit Repayment
        $repayment = LoanRepayment::first();
        $this->actingAs($this->treasurer);
        $this->put(route('treasurer.loans.repayments.update', [$loan, $repayment]), [
            'amount' => 8000,
            'payment_date' => '2026-06-20',
        ])->assertRedirect(route('treasurer.loans.show', $loan));

        $this->assertEquals(80000, $loan->fresh()->outstanding_balance); // 88000 - 8000 repayment

        // 5. Delete Repayment
        $this->delete(route('treasurer.loans.repayments.destroy', [$loan, $repayment]))
            ->assertRedirect(route('treasurer.loans.show', $loan));

        $this->assertEquals(8, $loan->fresh()->remaining_months); // Incremented back
        $this->assertEquals(88000, $loan->fresh()->outstanding_balance);

        // 6. Delete Loan
        $this->delete(route('treasurer.loans.destroy', $loan))
            ->assertRedirect(route('treasurer.loans.index'));

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_repayments', 0);
    }

    /** @test */
    public function admin_and_treasurer_can_edit_and_delete_investments_and_returns()
    {
        $this->actingAs($this->admin);

        // Create investment
        $this->post(route('admin.investments.store'), [
            'name' => 'Rice Importation',
            'type' => 'buying_selling_goods',
            'capital_amount' => 500000,
            'start_date' => '2026-06-01',
            'description' => 'Buying bags of rice for sale',
        ])->assertRedirect(route('admin.investments.index'));

        $investment = Investment::first();

        // Record Return
        $this->post(route('admin.investments.return', $investment), [
            'amount' => 50000,
            'return_date' => '2026-06-10',
            'description' => 'First week sales profit',
        ]);

        $this->assertEquals(50000, $investment->fresh()->total_returns);

        // Edit Investment details
        $this->put(route('admin.investments.update', $investment), [
            'name' => 'Rice Importation Updated',
            'type' => 'buying_selling_goods',
            'capital_amount' => 600000,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
            'description' => 'Updated desc',
        ])->assertRedirect(route('admin.investments.show', $investment));

        $this->assertEquals(600000, $investment->fresh()->capital_amount);

        // Edit Return
        $return = InvestmentReturn::first();
        $this->actingAs($this->treasurer);
        $this->put(route('treasurer.investments.returns.update', [$investment, $return]), [
            'amount' => 75000,
            'return_date' => '2026-06-15',
            'description' => 'First week sales profit revised',
        ])->assertRedirect(route('treasurer.investments.show', $investment));

        $this->assertEquals(75000, $investment->fresh()->total_returns);

        // Delete investment
        $this->delete(route('treasurer.investments.destroy', $investment))
            ->assertRedirect(route('treasurer.investments.index'));

        $this->assertDatabaseCount('investments', 0);
        $this->assertDatabaseCount('investment_returns', 0);
    }

    /** @test */
    public function member_cannot_access_any_data_correction_routes()
    {
        // Create dummy records to bypass Route Model Binding 404s
        $charge = RunningCharge::create([
            'user_id' => $this->member->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'payment_date' => '2026-06-01',
            'status' => 'paid',
            'recorded_by' => $this->admin->id,
        ]);
        
        $loan = Loan::create([
            'user_id' => $this->member->id,
            'principal_amount' => 100000,
            'profit_rate' => 20,
            'total_amount' => 120000,
            'monthly_payment' => 10000,
            'duration_months' => 12,
            'remaining_months' => 12,
            'date_granted' => '2026-06-01',
            'status' => 'active',
        ]);
        
        $investment = Investment::create([
            'name' => 'Test Investment',
            'type' => 'buying_selling_goods',
            'capital_amount' => 100000,
            'start_date' => '2026-06-01',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->member);

        // Savings edit
        $this->get(route('admin.savings.edit', ['user_id' => $this->member->id, 'month' => '2026-06-01']))->assertStatus(403);
        $this->put(route('admin.savings.update'), [])->assertStatus(403);
        $this->delete(route('admin.savings.destroy'), [])->assertStatus(403);

        // Running charges edit
        $this->get("/admin/running-charges/{$charge->id}/edit")->assertStatus(403);
        $this->put("/admin/running-charges/{$charge->id}", [])->assertStatus(403);
        $this->delete("/admin/running-charges/{$charge->id}")->assertStatus(403);

        // Loans edit
        $this->get(route('admin.loans.edit', $loan))->assertStatus(403);
        $this->put(route('admin.loans.update', $loan), [])->assertStatus(403);
        $this->delete(route('admin.loans.destroy', $loan))->assertStatus(403);

        // Investments edit
        $this->get(route('admin.investments.edit', $investment))->assertStatus(403);
        $this->put(route('admin.investments.update', $investment), [])->assertStatus(403);
        $this->delete(route('admin.investments.destroy', $investment))->assertStatus(403);
    }
}
