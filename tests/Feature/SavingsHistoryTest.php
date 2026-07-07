<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\MonthlySaving;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_savings_history_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.savings.history'));

        $response->assertOk();
    }

    public function test_admin_can_filter_savings_history_by_member_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001'
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith',
            'member_code' => 'YLDA/26/0002'
        ]);

        $slot1 = SavingsSlot::create(['user_id' => $member1->id, 'slot_number' => 1, 'is_active' => true]);
        $slot2 = SavingsSlot::create(['user_id' => $member2->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member1->id,
            'savings_slot_id' => $slot1->id,
            'amount' => 2000,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        MonthlySaving::create([
            'user_id' => $member2->id,
            'savings_slot_id' => $slot2->id,
            'amount' => 2000,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        // Filter by 'Alice'
        $response = $this->actingAs($admin)
            ->get(route('admin.savings.history', ['search' => 'Alice']));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Smith');
        $response->assertSee('₦2,000.00'); // Sum of Alice's savings
    }

    public function test_admin_can_filter_savings_history_by_member_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001'
        ]);
        $member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith',
            'member_code' => 'YLDA/26/0002'
        ]);

        $slot1 = SavingsSlot::create(['user_id' => $member1->id, 'slot_number' => 1, 'is_active' => true]);
        $slot2 = SavingsSlot::create(['user_id' => $member2->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member1->id,
            'savings_slot_id' => $slot1->id,
            'amount' => 2000,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        MonthlySaving::create([
            'user_id' => $member2->id,
            'savings_slot_id' => $slot2->id,
            'amount' => 2000,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        // Filter by member code '0002'
        $response = $this->actingAs($admin)
            ->get(route('admin.savings.history', ['search' => '0002']));

        $response->assertOk();
        $response->assertSee('Bob Smith');
        $response->assertDontSee('Alice Cooper');
        $response->assertSee('₦2,000.00');
    }

    public function test_admin_can_filter_savings_history_by_month_and_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001'
        ]);

        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // June 2026
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        // May 2026
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 3000,
            'month' => '2026-05-01',
            'status' => 'paid',
            'payment_date' => '2026-05-05'
        ]);

        // June 2025
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 4000,
            'month' => '2025-06-01',
            'status' => 'paid',
            'payment_date' => '2025-06-05'
        ]);

        // Filter by month = 6 (June)
        $response = $this->actingAs($admin)
            ->get(route('admin.savings.history', ['month' => 6]));

        $response->assertOk();
        $response->assertSee('Jun 2026');
        $response->assertSee('Jun 2025');
        $response->assertDontSee('May 2026');
        $response->assertSee('₦6,000.00'); // 2000 + 4000

        // Filter by year = 2025
        $response = $this->actingAs($admin)
            ->get(route('admin.savings.history', ['year' => 2025]));

        $response->assertOk();
        $response->assertSee('Jun 2025');
        $response->assertDontSee('Jun 2026');
        $response->assertDontSee('May 2026');
        $response->assertSee('₦4,000.00');

        // Filter by month = 6 and year = 2026
        $response = $this->actingAs($admin)
            ->get(route('admin.savings.history', ['month' => 6, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Jun 2026');
        $response->assertDontSee('Jun 2025');
        $response->assertDontSee('May 2026');
        $response->assertSee('₦2,000.00');
    }

    public function test_member_can_see_backdated_savings_history_on_dashboard(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001',
            'registration_year' => 2026,
            'created_at' => '2026-07-02 12:00:00' // Created today
        ]);

        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // Create savings in May (before created_at date)
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2026-05-01',
            'status' => 'paid',
            'payment_date' => '2026-05-05'
        ]);

        $response = $this->actingAs($member)
            ->get(route('member.savings'));

        $response->assertOk();
        $response->assertSee('May 2026');
        $response->assertSee('₦2,000.00');
        $response->assertSee('1 Slot');
    }
}
