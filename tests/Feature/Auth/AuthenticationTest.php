<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Selamat Datang di Jariyah Batang Hari')
            ->assertSee('Email / Username')
            ->assertSee('Daftar di sini');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->nazir()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_authenticate_using_email_in_any_case(): void
    {
        $user = User::factory()->nazir()->create(['email' => 'nazir@example.com']);

        $this->post(route('login.store'), [
            'email' => 'NAZIR@Example.com',
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->bpn()->create(['username' => 'petugas.bpn']);

        $response = $this->post(route('login.store'), [
            'email' => 'petugas.bpn',
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_unknown_username(): void
    {
        $this->post(route('login.store'), [
            'email' => 'tidak-ada',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->bpn()->nonaktif()->create(['username' => 'nonaktif']);

        foreach ([$user->email, 'nonaktif'] as $login) {
            $response = $this->from(route('login'))->post(route('login.store'), [
                'email' => $login,
                'password' => 'password',
            ]);

            $response
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi Admin BPN.']);

            $this->assertGuest();
        }

        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSee('Akun Anda dinonaktifkan. Hubungi Admin BPN.');

        $this->assertGuest();
    }

    public function test_inactive_status_is_not_revealed_with_wrong_password(): void
    {
        $user = User::factory()->nonaktif()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_per_login_input(): void
    {
        $user = User::factory()->bpn()->create(['username' => 'dibatasi']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'dibatasi', 'password' => 'salah']);
        }

        $this->post(route('login.store'), ['email' => 'dibatasi', 'password' => 'password'])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_nazir_lands_on_nazir_dashboard_after_login(): void
    {
        $user = User::factory()->nazir()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))->assertRedirect(route('nazir.dashboard'));
        $this->followingRedirects()->get(route('dashboard'))->assertOk();
    }

    public function test_bpn_lands_on_admin_dashboard_after_login(): void
    {
        $user = User::factory()->bpn()->create(['username' => 'bpn.dashboard']);

        $this->post(route('login.store'), ['email' => 'bpn.dashboard', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->followingRedirects()->get(route('dashboard'))->assertOk();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('beranda'));

        $this->assertGuest();
    }
}
