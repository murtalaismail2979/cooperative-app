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

    public function test_record_savings_link_preselects_member_and_provides_savings_info_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2026-05-01',
            'status' => 'paid',
            'payment_date' => '2026-05-05'
        ]);

        // 1. Visit Record Savings with member_id
        $createResponse = $this->actingAs($admin)->get(route('admin.savings.create', ['member_id' => $member->id]));
        $createResponse->assertOk();
        $createResponse->assertSee('value="' . $member->id . '" selected', false);

        // 2. Query savings-info API
        $infoResponse = $this->actingAs($admin)->get(route('members.savings-info', $member));
        $infoResponse->assertOk();
        $infoResponse->assertJson([
            'active_slots_count' => 1,
            'total_savings' => 2000,
            'formatted_total_savings' => '₦2,000.00',
            'latest_month' => 'May 2026',
            'latest_amount' => '₦2,000.00',
            'suggested_next_month' => '2026-06',
        ]);
    }

    public function test_member_can_filter_savings_by_year(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'registration_year' => 2025,
        ]);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2025-06-01',
            'status' => 'paid',
            'payment_date' => '2025-06-05'
        ]);

        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        // Filter by 2025
        $res2025 = $this->actingAs($member)->get(route('member.savings', ['year' => 2025]));
        $res2025->assertOk();
        $res2025->assertSee('June 2025');
        $res2025->assertDontSee('June 2026');

        // Filter by 2026
        $res2026 = $this->actingAs($member)->get(route('member.savings', ['year' => 2026]));
        $res2026->assertOk();
        $res2026->assertSee('June 2026');
        $res2026->assertDontSee('June 2025');
    }

    public function test_inactive_members_have_disabled_record_button_and_cannot_record_savings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inactiveMember = User::factory()->create(['role' => 'member', 'is_active' => false, 'name' => 'Inactive User']);
        $slot = SavingsSlot::create(['user_id' => $inactiveMember->id, 'slot_number' => 1, 'is_active' => true]);

        // 1. Admin savings index shows disabled button for inactive member
        $indexResponse = $this->actingAs($admin)->get(route('admin.savings.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Inactive User');
        $indexResponse->assertSee('disabled', false);

        // 2. Attempting to store savings for inactive member fails validation
        $storeResponse = $this->actingAs($admin)->post(route('admin.savings.store'), [
            'member_id' => $inactiveMember->id,
            'month' => '2026-06',
            'slots' => [$slot->id],
            'payment_date' => '2026-06-05'
        ]);

        $storeResponse->assertSessionHasErrors('member_id');
        $this->assertDatabaseMissing('monthly_savings', [
            'user_id' => $inactiveMember->id,
            'month' => '2026-06-01'
        ]);
    }

    public function test_member_savings_page_displays_jan_dec_savings_status_quick_view(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'registration_year' => 2026,
        ]);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // Create savings for Jan, Feb, Mar 2026
        foreach ([1 => '2026-01-05', 2 => '2026-02-05', 3 => '2026-03-05'] as $m => $date) {
            MonthlySaving::create([
                'user_id' => $member->id,
                'savings_slot_id' => $slot->id,
                'amount' => 5000,
                'month' => sprintf('2026-%02d-01', $m),
                'status' => 'paid',
                'payment_date' => $date,
            ]);
        }

        $response = $this->actingAs($member)->get(route('member.savings', ['year' => 2026]));

        $response->assertOk();
        $response->assertSee('JAN - DEC SAVINGS STATUS (2026)');
        $response->assertSee('bi-check-circle-fill');
        $response->assertSee('bi-x-circle-fill');
        $response->assertSee('Months Paid in 2026');
        $response->assertSee('Total Saved in 2026');
        $response->assertSee('₦15,000.00');
        $response->assertSee('3');
    }

    public function test_savings_history_and_quick_view_respects_october_2021_inception_and_has_no_records_for_jan_to_sep_2021(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'registration_year' => 2021,
            'created_at' => '2021-01-01 00:00:00',
        ]);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // Add savings only for Oct and Nov 2021
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2021-10-01',
            'status' => 'paid',
            'payment_date' => '2021-10-05',
        ]);
        MonthlySaving::create([
            'user_id' => $member->id,
            'savings_slot_id' => $slot->id,
            'amount' => 2000,
            'month' => '2021-11-01',
            'status' => 'paid',
            'payment_date' => '2021-11-05',
        ]);

        $response = $this->actingAs($member)->get(route('member.savings', ['year' => 2021]));
        $response->assertOk();

        // 2021 possible months should be 3 (Oct, Nov, Dec), not 12
        $response->assertSee('/ 3');
        // Check Before Inception badge text exists for Jan-Sep
        $response->assertSee('Before Inception (Pre-Oct 2021)');

        // In detailed transaction history table, no months prior to October 2021 should appear
        $response->assertDontSee('January 2021');
        $response->assertDontSee('February 2021');
        $response->assertDontSee('March 2021');
        $response->assertDontSee('September 2021');
        $response->assertSee('October 2021');
        $response->assertSee('November 2021');
    }
}
