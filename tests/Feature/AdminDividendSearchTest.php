<?php

namespace Tests\Feature;

use App\Models\Dividend;
use App\Models\DividendPayout;
use App\Models\Investment;
use App\Models\MonthlySaving;
use App\Models\SavingsSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDividendSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member1;
    protected User $member2;
    protected Investment $investment1;
    protected Investment $investment2;
    protected Dividend $dividend1;
    protected Dividend $dividend2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->member1 = User::factory()->create([
            'role' => 'member',
            'name' => 'Alice Cooper',
            'member_code' => 'YLDA/26/0001',
        ]);

        $this->member2 = User::factory()->create([
            'role' => 'member',
            'name' => 'Bob Smith',
            'member_code' => 'YLDA/26/0002',
        ]);

        // Savings for members so dividends can be created
        $slot1 = SavingsSlot::create(['user_id' => $this->member1->id, 'slot_number' => 1, 'is_active' => true]);
        $slot2 = SavingsSlot::create(['user_id' => $this->member2->id, 'slot_number' => 1, 'is_active' => true]);

        MonthlySaving::create([
            'user_id' => $this->member1->id,
            'savings_slot_id' => $slot1->id,
            'amount' => 2000,
            'month' => '2026-01-01',
            'status' => 'paid',
            'payment_date' => '2026-01-05',
        ]);

        MonthlySaving::create([
            'user_id' => $this->member2->id,
            'savings_slot_id' => $slot2->id,
            'amount' => 2000,
            'month' => '2026-01-01',
            'status' => 'paid',
            'payment_date' => '2026-01-05',
        ]);

        // Create Investments
        $this->investment1 = Investment::create([
            'name' => 'Solar Energy Venture',
            'type' => 'business',
            'capital_amount' => 500000,
            'total_returns' => 600000,
            'start_date' => '2026-01-01',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->investment2 = Investment::create([
            'name' => 'Real Estate Development',
            'type' => 'financing',
            'capital_amount' => 1000000,
            'total_returns' => 1200000,
            'start_date' => '2026-02-01',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        // Create Dividends
        $this->dividend1 = Dividend::create([
            'year' => 2026,
            'total_dividend_amount' => 100000,
            'total_units' => 2,
            'unit_value' => 50000,
            'distributed_at' => '2026-03-01 10:00:00',
            'distributed_by' => $this->admin->id,
            'investment_id' => $this->investment1->id,
            'original_sharable_profit' => 100000,
            'cooperative_amount' => 5000,
            'management_amount' => 5000,
            'member_distribution_pool' => 90000,
        ]);

        $this->dividend2 = Dividend::create([
            'year' => 2025,
            'total_dividend_amount' => 200000,
            'total_units' => 2,
            'unit_value' => 100000,
            'distributed_at' => '2026-04-01 10:00:00',
            'distributed_by' => $this->admin->id,
            'investment_id' => $this->investment2->id,
            'original_sharable_profit' => 200000,
            'cooperative_amount' => 10000,
            'management_amount' => 10000,
            'member_distribution_pool' => 180000,
        ]);

        // Create Payouts
        DividendPayout::create([
            'dividend_id' => $this->dividend1->id,
            'user_id' => $this->member1->id,
            'units' => 1,
            'amount' => 45000,
            'paid' => true,
            'paid_date' => '2026-03-02',
        ]);

        DividendPayout::create([
            'dividend_id' => $this->dividend1->id,
            'user_id' => $this->member2->id,
            'units' => 1,
            'amount' => 45000,
            'paid' => true,
            'paid_date' => '2026-03-02',
        ]);

        DividendPayout::create([
            'dividend_id' => $this->dividend2->id,
            'user_id' => $this->member1->id,
            'units' => 1,
            'amount' => 90000,
            'paid' => false,
            'paid_date' => null,
        ]);

        DividendPayout::create([
            'dividend_id' => $this->dividend2->id,
            'user_id' => $this->member2->id,
            'units' => 1,
            'amount' => 90000,
            'paid' => false,
            'paid_date' => null,
        ]);
    }

    public function test_non_admin_cannot_access_admin_dividends_search(): void
    {
        $response = $this->actingAs($this->member1)->get(route('admin.dividends.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_shared_dividends(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index'));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(2, $dividends->total());
    }

    public function test_admin_can_filter_dividends_by_investment_id(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'investment_id' => $this->investment1->id,
        ]));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(1, $dividends->total());
        $this->assertEquals($this->investment1->id, $dividends->first()->investment_id);
    }

    public function test_admin_can_filter_dividends_by_investment_name(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'investment_name' => 'Real Estate',
        ]));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(1, $dividends->total());
        $this->assertEquals($this->investment2->id, $dividends->first()->investment_id);
    }

    public function test_admin_can_filter_dividends_by_member_name_or_code(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'member_name' => 'Alice',
        ]));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(2, $dividends->total());
    }

    public function test_admin_can_filter_dividends_by_status(): void
    {
        // Filter by 'paid' (fully paid dividends)
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'status' => 'paid',
        ]));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(1, $dividends->total());
        $this->assertEquals($this->dividend1->id, $dividends->first()->id);

        // Filter by 'pending' (dividends with pending payouts)
        $responsePending = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'status' => 'pending',
        ]));

        $responsePending->assertOk();
        $dividendsPending = $responsePending->original->getData()['dividends'];
        $this->assertEquals(1, $dividendsPending->total());
        $this->assertEquals($this->dividend2->id, $dividendsPending->first()->id);
    }

    public function test_admin_can_filter_dividends_by_date_range(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'start_date' => '2026-03-15',
            'end_date' => '2026-04-15',
        ]));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(1, $dividends->total());
        $this->assertEquals($this->dividend2->id, $dividends->first()->id);
    }

    public function test_api_json_resource_returns_filtered_dividends(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.dividends.index', ['investment_id' => $this->investment1->id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'year',
                    'total_dividend_amount',
                    'total_units',
                    'unit_value',
                    'distributed_at',
                    'investment',
                ],
            ],
        ]);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->investment1->id, $response->json('data.0.investment_id'));
    }

    public function test_member_dividend_view_remains_scoped_to_logged_in_member(): void
    {
        $response = $this->actingAs($this->member1)->get(route('member.dividends'));

        $response->assertOk();
        $response->assertSee('Alice Cooper');
        $response->assertDontSee('Bob Smith');
    }

    public function test_admin_can_filter_dividends_by_year(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dividends.index', [
            'year' => 2025,
        ]));

        $response->assertOk();
        $dividends = $response->original->getData()['dividends'];
        $this->assertEquals(1, $dividends->total());
        $this->assertEquals(2025, $dividends->first()->year);

        $memberSummaries = $response->original->getData()['memberSummaries'];
        $aliceSummary = $memberSummaries->firstWhere('id', $this->member1->id);
        // For year 2025, Alice's dividend payout amount is 90000
        $this->assertEquals(90000, $aliceSummary->total_earned);
    }
}
