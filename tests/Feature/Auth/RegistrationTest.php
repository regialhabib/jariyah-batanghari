<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataValid(array $override = []): array
    {
        return [
            'name' => 'Ahmad Nazir',
            'nik' => '1504012345678901',
            'email' => 'nazir@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
            ...$override,
        ];
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('Bergabung bersama Jariyah Batang Hari')
            ->assertSee('NIK')
            ->assertSee('No. HP')
            ->assertSee('Saya menyetujui Syarat dan Ketentuan yang berlaku')
            ->assertDontSee('name="role"', false);
    }

    public function test_new_users_can_register_as_nazir(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), $this->dataValid());

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'nazir@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::Nazir->value));
        $this->assertSame('1504012345678901', $user->nik);
        $this->assertSame('081234567890', $user->phone);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_nazir_lands_on_verify_email_notice_after_registration(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->dataValid());

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('nazir.dashboard'))->assertRedirect(route('verification.notice'));

        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Verifikasi Email');
    }

    public function test_role_from_input_is_ignored(): void
    {
        $this->post(route('register.store'), $this->dataValid([
            'role' => Role::AdminBpn->value,
            'roles' => [Role::AdminBpn->value],
            'is_active' => '0',
            'kecamatan_id' => '1',
            'username' => 'penyusup',
        ]))->assertSessionHasNoErrors();

        $user = User::where('email', 'nazir@example.com')->firstOrFail();

        $this->assertSame([Role::Nazir->value], $user->getRoleNames()->all());
        $this->assertFalse($user->hasRole(Role::AdminBpn->value));
        $this->assertTrue($user->is_active);
        $this->assertNull($user->kecamatan_id);
        $this->assertNull($user->username);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nikTidakValid(): array
    {
        return [
            'kurang dari 16 digit' => ['150401234567890'],
            'lebih dari 16 digit' => ['15040123456789012'],
            'mengandung huruf' => ['15040123456789AB'],
        ];
    }

    #[DataProvider('nikTidakValid')]
    public function test_nik_must_be_16_digits(string $nik): void
    {
        $this->post(route('register.store'), $this->dataValid(['nik' => $nik]))
            ->assertSessionHasErrors('nik');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_nik_must_be_unique(): void
    {
        User::factory()->nazir()->create(['nik' => '1504012345678901']);

        $this->post(route('register.store'), $this->dataValid())
            ->assertSessionHasErrors('nik');

        $this->assertGuest();
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'nazir@example.com']);

        $this->post(route('register.store'), $this->dataValid())
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function noHpTidakValid(): array
    {
        return [
            'bukan nomor seluler' => ['0217654321'],
            'terlalu pendek' => ['08123'],
            'mengandung huruf' => ['08123abc4567'],
            'kode negara lain' => ['+6012345678901'],
        ];
    }

    #[DataProvider('noHpTidakValid')]
    public function test_phone_format_is_validated(string $phone): void
    {
        $this->post(route('register.store'), $this->dataValid(['phone' => $phone]))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function noHpValid(): array
    {
        return [
            'awalan 08' => ['081234567890'],
            'awalan 62' => ['6281234567890'],
            'awalan +62' => ['+6281234567890'],
        ];
    }

    #[DataProvider('noHpValid')]
    public function test_valid_indonesian_phone_numbers_are_accepted(string $phone): void
    {
        $this->post(route('register.store'), $this->dataValid(['phone' => $phone]))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_phone_is_required(): void
    {
        $data = $this->dataValid();
        unset($data['phone']);

        $this->post(route('register.store'), $data)->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_terms_must_be_accepted(): void
    {
        $data = $this->dataValid();
        unset($data['terms']);

        $this->post(route('register.store'), $data)
            ->assertSessionHasErrors(['terms' => 'Anda harus menyetujui Syarat dan Ketentuan yang berlaku.']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->post(route('register.store'), $this->dataValid(['password_confirmation' => 'berbeda123']))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }
}
