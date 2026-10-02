<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SavingsSlot;
use App\Models\RunningCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreasurerRunningChargesTest extends TestCase
{
    use RefreshDatabase;

    public function test_treasurer_can_access_running_charges_index_page(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        $response = $this->actingAs($treasurer)
            ->get(route('treasurer.running-charges.index'));

        $response->assertOk();
        $response->assertSee('Running Charges');
        $response->assertSee('Recent Charges');
        $response->assertSee($member->name);
        $response->assertSee('Total: ₦500.00');
    }

    public function test_admin_can_access_running_charges_index_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 1200,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.running-charges.index'));

        $response->assertOk();
        $response->assertSee('Running Charges');
        $response->assertSee('Recent Charges');
        $response->assertSee($member->name);
        $response->assertSee('Total: ₦1,200.00');
    }

    public function test_treasurer_can_access_running_charges_history_page(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        RunningCharge::create([
            'user_id' => $member->id,
            'amount' => 500,
            'month' => '2026-06-01',
            'status' => 'paid',
            'payment_date' => '2026-06-05'
        ]);

        $response = $this->actingAs($treasurer)
            ->get(route('treasurer.running-charges.history'));

        $response->assertOk();
        $response->assertSee('Running Charges History');
        $response->assertSee('Total Collected (Filtered)');
        $response->assertSee($member->name);
    }

    public function test_treasurer_recording_savings_redirects_to_running_charges_index(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.savings.store'), [
                'user_id' => $member->id,
                'month' => '2026-06-01',
                'slot_ids' => [$slot->id],
                'payment_date' => '2026-06-01'
            ]);

        $response->assertRedirect(route('treasurer.running-charges.index'));
        $response->assertSessionHas('success', 'Monthly savings recorded successfully.');

        // Verify running charge was recorded immediately
        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'month' => '2026-06-01 00:00:00',
            'amount' => 500,
        ]);
    }

    public function test_admin_recording_savings_redirects_to_running_charges_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'member_id' => $member->id,
                'month' => '2026-06-01',
                'slots' => [$slot->id],
                'payment_date' => '2026-06-01'
            ]);

        $response->assertRedirect(route('admin.running-charges.index'));
        $response->assertSessionHas('success', 'Savings recorded successfully.');

        // Verify running charge was recorded immediately
        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'month' => '2026-06-01 00:00:00',
            'amount' => 500,
        ]);
    }

    public function test_treasurer_recording_running_charges_manually_redirects_to_running_charges_index(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($treasurer)
            ->post(route('treasurer.running-charges.store'), [
                'user_id' => $member->id,
                'month' => '2026-06-01',
                'payment_date' => '2026-06-15',
            ]);

        $response->assertRedirect(route('treasurer.running-charges.index'));
        $response->assertSessionHas('success', 'Running charge recorded successfully.');
    }

    public function test_non_treasurer_cannot_access_treasurer_running_charges_pages(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->get(route('treasurer.running-charges.index'))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('treasurer.running-charges.history'))
            ->assertForbidden();
    }

    public function test_running_charge_amount_defaults_by_year_when_saving(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $slot = SavingsSlot::create(['user_id' => $member->id, 'slot_number' => 1, 'is_active' => true]);

        // Savings in 2021 -> should record 100
        $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'member_id' => $member->id,
                'month' => '2021-06-01',
                'slots' => [$slot->id],
                'payment_date' => '2021-06-01'
            ]);

        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'month' => '2021-06-01 00:00:00',
            'amount' => 100.00,
        ]);

        // Savings in 2023 -> should record 300
        $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'member_id' => $member->id,
                'month' => '2023-06-01',
                'slots' => [$slot->id],
                'payment_date' => '2023-06-01'
            ]);

        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'month' => '2023-06-01 00:00:00',
            'amount' => 300.00,
        ]);

        // Savings in 2025 -> should record 500
        $this->actingAs($admin)
            ->post(route('admin.savings.store'), [
                'member_id' => $member->id,
                'month' => '2025-06-01',
                'slots' => [$slot->id],
                'payment_date' => '2025-06-01'
            ]);

        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'month' => '2025-06-01 00:00:00',
            'amount' => 500.00,
        ]);
    }

    public function test_admin_and_treasurer_can_manually_record_custom_running_charge_amount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // Record 100 manually for 2025
        $this->actingAs($admin)
            ->post(route('admin.running-charges.store'), [
                'user_id' => $member->id,
                'month' => '2025-06-01',
                'amount' => 100.00,
                'payment_date' => '2025-06-20',
            ]);

        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'month' => '2025-06-01 00:00:00',
            'amount' => 100.00,
        ]);
    }
}
