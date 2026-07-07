<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RunningCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunningChargesHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_running_charges_history_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 1000,
            'month' => '2025-06-01',
            'status' => 'paid',
            'payment_date' => '2025-06-05'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.history'));

        $response->assertOk();
        $response->assertSee('Monthly Summary of Total Charges');
        $response->assertSee('June 2026');
        $response->assertSee('₦500.00');
        $response->assertSee('June 2025');
        $response->assertSee('₦1,000.00');
    }

    public function test_admin_can_filter_running_charges_by_member_name_and_code(): void
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

        RunningCharge::create([
            'user_id' => $member1->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        RunningCharge::create([
            'user_id' => $member2->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        // Filter by member name 'John'
        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.history', ['member' => 'John']));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
        $response->assertSee('₦500.00'); // Total of filtered charges

        // Filter by member code '0002'
        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.history', ['member' => '0002']));

        $response->assertOk();
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');
    }

    public function test_admin_can_filter_running_charges_by_month_and_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'John Doe',
            'member_code' => 'YLDA/26/0001'
        ]);

        // June 2026
        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        // May 2026
        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500,
            'month' => '2026-05-01',
            'status' => 'paid',
            'payment_date' => '2026-05-05'
        ]);

        // June 2025
        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500,
            'month' => '2025-06-01',
            'status' => 'paid',
            'payment_date' => '2025-06-05'
        ]);

        // Filter by month = 6 (June)
        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.history', ['month' => 6]));

        $response->assertOk();
        $response->assertSee('June 2026');
        $response->assertSee('June 2025');
        $response->assertDontSee('May 2026');
        $response->assertSee('₦1,000.00'); // Sum of both June records

        // Filter by year = 2025
        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.history', ['year' => 2025]));

        $response->assertOk();
        $response->assertSee('June 2025');
        $response->assertDontSee('June 2026');
        $response->assertDontSee('May 2026');
        $response->assertSee('₦500.00'); // Sum of 2025 record

        // Filter by month = 6 and year = 2026
        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.history', ['month' => 6, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('June 2026');
        $response->assertDontSee('June 2025');
        $response->assertDontSee('May 2026');
        $response->assertSee('₦500.00');
    }
}
