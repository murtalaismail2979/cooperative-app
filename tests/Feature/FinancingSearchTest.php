<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancingSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_loans_by_member_name_and_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'John Doe',
            'member_code' => 'YLDA/26/0001'
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Jane Smith',
            'member_code' => 'YLDA/26/0002'
        ]);

        // Create loans for members using factories or direct creation
        $loan1 = Loan::create([
            'user_id' => $member1->id,
            'principal_amount' => 50000,
            'total_amount' => 55000,
            'monthly_payment' => 5500,
            'outstanding_balance' => 55000,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-06-01',
            'recorded_by' => $admin->id
        ]);

        $loan2 = Loan::create([
            'user_id' => $member2->id,
            'principal_amount' => 100000,
            'total_amount' => 110000,
            'monthly_payment' => 11000,
            'outstanding_balance' => 110000,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-06-01',
            'recorded_by' => $admin->id
        ]);

        // Search by member name 'John'
        $response = $this->actingAs($admin)
            ->get(route('admin.loans.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertDontSee('<td>Jane Smith</td>', false);

        // Search by member code '0002'
        $response = $this->actingAs($admin)
            ->get(route('admin.loans.index', ['search' => '0002']));

        $response->assertOk();
        $response->assertSee('Jane Smith');
        $response->assertDontSee('<td>John Doe</td>', false);
    }

    public function test_treasurer_can_search_loans_by_member_name_and_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0003'
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith',
            'member_code' => 'YLDA/26/0004'
        ]);

        $loan1 = Loan::create([
            'user_id' => $member1->id,
            'principal_amount' => 40000,
            'total_amount' => 44000,
            'monthly_payment' => 4400,
            'outstanding_balance' => 44000,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-06-01',
            'recorded_by' => $admin->id
        ]);

        $loan2 = Loan::create([
            'user_id' => $member2->id,
            'principal_amount' => 80000,
            'total_amount' => 88000,
            'monthly_payment' => 8800,
            'outstanding_balance' => 88000,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-06-01',
            'recorded_by' => $admin->id
        ]);

        // Search by member name 'Alice'
        $response = $this->actingAs($treasurer)
            ->get(route('treasurer.loans.index', ['search' => 'Alice']));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('<td>Bob Smith</td>', false);

        // Search by member code '0004'
        $response = $this->actingAs($treasurer)
            ->get(route('treasurer.loans.index', ['search' => '0004']));

        $response->assertOk();
        $response->assertSee('Bob Smith');
        $response->assertDontSee('<td>Alice Cooper</td>', false);
    }
}
