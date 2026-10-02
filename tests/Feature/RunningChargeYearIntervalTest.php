<?php

namespace Tests\Feature;

use App\Models\RunningCharge;
use App\Models\RunningChargeRate;
use App\Models\User;
use App\Services\RunningChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunningChargeYearIntervalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_charges_for_year_interval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        // Create charges across different years
        $charge1 = RunningCharge::create([
            'user_id' => $member->id,
            'month' => '2024-03-01',
            'amount' => 500,
            'payment_date' => '2024-03-05',
            'status' => 'paid',
        ]);

        $charge2 = RunningCharge::create([
            'user_id' => $member->id,
            'month' => '2025-06-01',
            'amount' => 500,
            'payment_date' => '2025-06-05',
            'status' => 'paid',
        ]);

        $chargeOutside = RunningCharge::create([
            'user_id' => $member->id,
            'month' => '2021-01-01',
            'amount' => 100,
            'payment_date' => '2021-01-05',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.running-charges.update-interval'), [
            'start_year' => 2024,
            'end_year' => 2025,
            'amount' => 650,
        ]);

        $response->assertRedirect(route('admin.running-charges.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(650.00, $charge1->fresh()->amount);
        $this->assertEquals(650.00, $charge2->fresh()->amount);
        $this->assertEquals(100.00, $chargeOutside->fresh()->amount);
    }

    public function test_admin_can_edit_2022_2023_year_interval_rate_from_300_to_new_amount(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $charge2022 = RunningCharge::create([
            'user_id' => $member->id,
            'month' => '2022-05-01',
            'amount' => 300,
            'payment_date' => '2022-05-05',
            'status' => 'paid',
        ]);

        $charge2023 = RunningCharge::create([
            'user_id' => $member->id,
            'month' => '2023-08-01',
            'amount' => 300,
            'payment_date' => '2023-08-05',
            'status' => 'paid',
        ]);

        // Update 2022-2023 interval from 300 to 400
        $response = $this->actingAs($admin)->post(route('admin.running-charges.update-interval'), [
            'start_year' => 2022,
            'end_year' => 2023,
            'amount' => 400,
        ]);

        $response->assertRedirect(route('admin.running-charges.index'));

        // Existing charges updated
        $this->assertEquals(400.00, $charge2022->fresh()->amount);
        $this->assertEquals(400.00, $charge2023->fresh()->amount);

        // Future rate lookup for 2022 and 2023 updated to 400
        $service = app(RunningChargeService::class);
        $this->assertEquals(400.00, $service->getAmountForYear(2022));
        $this->assertEquals(400.00, $service->getAmountForYear(2023));
    }

    public function test_admin_can_record_custom_charge_amount_for_any_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($admin)->post(route('admin.running-charges.store'), [
            'user_id' => $member->id,
            'month' => '2025-05-01',
            'amount' => 750,
            'payment_date' => '2025-05-02',
        ]);

        $response->assertRedirect(route('admin.running-charges.index'));

        $this->assertDatabaseHas('running_charges', [
            'user_id' => $member->id,
            'amount' => 750,
        ]);
    }
}
