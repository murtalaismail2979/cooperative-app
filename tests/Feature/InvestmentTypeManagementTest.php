<?php

namespace Tests\Feature;

use App\Models\Investment;
use App\Models\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_investment_types_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.investment-types.index'));

        $response->assertStatus(200);
        $response->assertViewHas('types');
    }

    public function test_admin_can_create_new_investment_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.investment-types.store'), [
                'name' => 'Real Estate Development',
            ]);

        $response->assertRedirect(route('admin.investment-types.index'));
        $this->assertDatabaseHas('investment_types', [
            'name' => 'Real Estate Development',
            'slug' => 'real_estate_development',
        ]);
    }

    public function test_admin_cannot_create_duplicate_investment_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        InvestmentType::create(['name' => 'Tech Startup', 'slug' => 'tech_startup']);

        $response = $this->actingAs($admin)
            ->post(route('admin.investment-types.store'), [
                'name' => 'Tech Startup',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_delete_unused_investment_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $type = InvestmentType::create(['name' => 'Automotive Fleet', 'slug' => 'automotive_fleet']);

        $response = $this->actingAs($admin)
            ->delete(route('admin.investment-types.destroy', $type));

        $response->assertRedirect(route('admin.investment-types.index'));
        $this->assertDatabaseMissing('investment_types', [
            'id' => $type->id,
        ]);
    }

    public function test_admin_cannot_delete_used_investment_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $type = InvestmentType::create(['name' => 'Crypto Fund', 'slug' => 'crypto_fund']);
        
        Investment::create([
            'name' => 'Bitcoin Holdings',
            'type' => 'crypto_fund',
            'capital_amount' => 5000.00,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'created_by' => $admin->id
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.investment-types.destroy', $type));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('investment_types', [
            'id' => $type->id,
        ]);
    }

    public function test_treasurer_cannot_access_investment_types_page(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $response = $this->actingAs($treasurer)
            ->get(route('admin.investment-types.index'));

        $response->assertStatus(403);
    }

    public function test_treasurer_cannot_create_investment_types(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer']);

        $response = $this->actingAs($treasurer)
            ->post(route('admin.investment-types.store'), [
                'name' => 'New Type By Treasurer',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_create_investment_with_custom_investment_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        InvestmentType::create(['name' => 'Venture Capital', 'slug' => 'venture_capital']);

        $response = $this->actingAs($admin)
            ->post(route('admin.investments.store'), [
                'name' => 'Silicon Valley Fund',
                'type' => 'venture_capital',
                'capital_amount' => 50000.00,
                'start_date' => '2026-06-01',
                'description' => 'A custom investment type test'
            ]);

        $response->assertRedirect(route('admin.investments.index'));
        $this->assertDatabaseHas('investments', [
            'name' => 'Silicon Valley Fund',
            'type' => 'venture_capital',
        ]);
    }
}
