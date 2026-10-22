<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }

    public function test_phone_can_be_updated_and_cleared(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('phone', '081298765432')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('081298765432', $user->refresh()->phone);

        Livewire::test('pages::settings.profile')
            ->assertSet('phone', '081298765432')
            ->set('phone', '')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertNull($user->refresh()->phone);
    }

    public function test_phone_format_is_validated(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $this->actingAs($user);

        Livewire::test('pages::settings.profile')
            ->set('phone', '12345')
            ->call('updateProfileInformation')
            ->assertHasErrors(['phone']);

        $this->assertSame('081234567890', $user->refresh()->phone);
    }

    public function test_nazir_sees_nik_read_only(): void
    {
        $user = User::factory()->nazir()->create(['nik' => '1504019876543210']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('1504019876543210');

        Livewire::test('pages::settings.profile')
            ->set('name', 'Nama Baru')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('1504019876543210', $user->refresh()->nik);
    }

    public function test_nik_is_not_shown_for_petugas(): void
    {
        $user = User::factory()->bpn()->create(['nik' => '1504019876543210']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('1504019876543210');
    }
}
