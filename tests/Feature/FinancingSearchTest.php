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

    public function test_admin_can_filter_loans_by_year_and_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'cooperative_id' => 1]);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Michael Scott', 'cooperative_id' => 1]);

        // Loan in March 2025
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 50000,
            'total_amount' => 55000,
            'monthly_payment' => 5500,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2025-03-15',
        ]);

        // Loan in August 2026
        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 90000,
            'total_amount' => 99000,
            'monthly_payment' => 9900,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-08-20',
        ]);

        // Filter by year 2025
        $response2025 = $this->actingAs($admin)->get(route('admin.loans.index', ['year' => 2025]));
        $response2025->assertOk();
        $response2025->assertSee('₦50,000.00');
        $response2025->assertDontSee('₦90,000.00');

        // Filter by year 2026 and month 8 (August)
        $response2026Aug = $this->actingAs($admin)->get(route('admin.loans.index', ['year' => 2026, 'month' => 8]));
        $response2026Aug->assertOk();
        $response2026Aug->assertSee('₦90,000.00');
        $response2026Aug->assertDontSee('₦50,000.00');

        // Filter by month 3 (March) in 2026 (should find none)
        $responseNone = $this->actingAs($admin)->get(route('admin.loans.index', ['year' => 2026, 'month' => 3]));
        $responseNone->assertOk();
        $responseNone->assertSee('No financing records found');
    }

    public function test_treasurer_can_filter_loans_by_year_and_month(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer', 'cooperative_id' => 1]);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Jim Halpert', 'cooperative_id' => 1]);

        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 30000,
            'total_amount' => 33000,
            'monthly_payment' => 3300,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2025-05-10',
        ]);

        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 70000,
            'total_amount' => 77000,
            'monthly_payment' => 7700,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-11-05',
        ]);

        $response = $this->actingAs($treasurer)->get(route('treasurer.loans.index', ['year' => 2026, 'month' => 11]));
        $response->assertOk();
        $response->assertSee('₦70,000.00');
        $response->assertDontSee('₦30,000.00');
    }

    public function test_member_can_filter_loans_by_year_and_month(): void
    {
        $member = User::factory()->create(['role' => 'member', 'cooperative_id' => 1]);

        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 25000,
            'total_amount' => 27500,
            'monthly_payment' => 2750,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2024-04-12',
        ]);

        Loan::create([
            'user_id' => $member->id,
            'principal_amount' => 60000,
            'total_amount' => 66000,
            'monthly_payment' => 6600,
            'duration_months' => 10,
            'remaining_months' => 10,
            'status' => 'active',
            'date_granted' => '2026-07-15',
        ]);

        $response = $this->actingAs($member)->get(route('member.loans', ['year' => 2026, 'month' => 7]));
        $response->assertOk();
        $response->assertSee('₦60,000.00');
        $response->assertDontSee('₦25,000.00');
    }
}
