<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_member_can_update_email_phone_and_address(): void
    {
        $member = User::factory()->create([
            'role' => 'member',
            'email' => 'oldmember@coop.test',
            'phone' => '08011112222',
            'address' => 'Old Address',
        ]);

        $response = $this
            ->actingAs($member)
            ->patch('/profile', [
                'name' => 'Updated Member Name',
                'email' => 'newmember@coop.test',
                'phone' => '08099998888',
                'address' => 'New Residential Address',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $member->refresh();
        $this->assertSame('Updated Member Name', $member->name);
        $this->assertSame('newmember@coop.test', $member->email);
        $this->assertSame('08099998888', $member->phone);
        $this->assertSame('New Residential Address', $member->address);
    }

    public function test_member_cannot_update_email_to_existing_user_email(): void
    {
        User::factory()->create(['email' => 'existing@coop.test']);
        $member = User::factory()->create(['role' => 'member', 'email' => 'member1@coop.test']);

        $response = $this
            ->actingAs($member)
            ->patch('/profile', [
                'name' => $member->name,
                'email' => 'existing@coop.test',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame('member1@coop.test', $member->fresh()->email);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_delete_account_button_is_not_displayed_for_members(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this
            ->actingAs($member)
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Delete Account');
        $response->assertDontSee('Danger Zone: Delete Account');
    }

    public function test_member_cannot_delete_their_account(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this
            ->actingAs($member)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertForbidden();
        $this->assertNotNull($member->fresh());
    }

    public function test_delete_account_button_is_not_displayed_for_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee('Delete Account');
        $response->assertDontSee('Danger Zone: Delete Account');
    }

    public function test_admin_cannot_delete_their_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this
            ->actingAs($admin)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertForbidden();
        $this->assertNotNull($admin->fresh());
    }
}
