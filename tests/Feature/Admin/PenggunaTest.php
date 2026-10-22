<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kelola Pengguna (Fase 7): hanya Admin BPN.
 */
class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    }

    public function test_bpn_dan_kua_mendapat_403_di_halaman_pengguna(): void
    {
        $bpn = User::factory()->bpn()->create();
        $kua = User::factory()->kua()->create();
        $target = User::factory()->bpn()->create();

        foreach ([$bpn, $kua] as $user) {
            $this->actingAs($user)->get(route('admin.pengguna.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.pengguna.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.pengguna.edit', $target))->assertForbidden();
        }

        $this->actingAs($bpn);
        Livewire::test('pages::admin.pengguna.index')->assertForbidden();
    }

    public function test_daftar_pengguna_dengan_filter(): void
    {
        $kec = Kecamatan::factory()->create(['nama' => 'Muara Tembesi']);
        User::factory()->kua($kec)->create(['name' => 'Petugas Tembesi']);
        User::factory()->nazir()->create(['name' => 'Nazir Satu']);
        User::factory()->bpn()->nonaktif()->create(['name' => 'Petugas Lama']);

        $this->actingAs($this->admin)->get(route('admin.pengguna.index'))
            ->assertOk()->assertSee('Petugas Tembesi')->assertSee('Muara Tembesi')->assertSee('Petugas KUA');

        $this->actingAs($this->admin);
        Livewire::test('pages::admin.pengguna.index')
            ->set('role', 'kua')
            ->assertSee('Petugas Tembesi')->assertDontSee('Nazir Satu')
            ->set('role', '')
            ->set('aktif', '0')
            ->assertSee('Petugas Lama')->assertDontSee('Petugas Tembesi')
            ->set('aktif', '')
            ->set('cari', 'Nazir')
            ->assertSee('Nazir Satu')->assertDontSee('Petugas Lama');
    }

    public function test_admin_membuat_petugas_kua_dengan_kecamatan_wajib(): void
    {
        $kec = Kecamatan::factory()->create();
        $this->actingAs($this->admin);

        $form = Livewire::test('pages::admin.pengguna.form')
            ->set('name', 'Petugas KUA Baru')
            ->set('username', 'kua_baru')
            ->set('email', 'kua.baru@example.com')
            ->set('role', 'kua')
            ->set('password', 'rahasia-123')
            ->set('password_confirmation', 'rahasia-123')
            ->call('simpan')
            ->assertHasErrors(['kecamatan_id' => 'required_if']);

        $form->set('kecamatan_id', (string) $kec->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.pengguna.index'));

        $user = User::query()->where('email', 'kua.baru@example.com')->firstOrFail();
        $this->assertTrue($user->isKua());
        $this->assertSame($kec->id, $user->kecamatan_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia-123', $user->password));
    }

    public function test_form_tidak_bisa_membuat_role_nazir(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.pengguna.form')
            ->set('name', 'Coba Nazir')
            ->set('username', 'coba_nazir')
            ->set('email', 'coba@example.com')
            ->set('role', Role::Nazir->value)
            ->set('password', 'rahasia-123')
            ->set('password_confirmation', 'rahasia-123')
            ->call('simpan')
            ->assertHasErrors(['role' => 'in']);

        $this->assertDatabaseMissing('users', ['email' => 'coba@example.com']);
    }

    public function test_petugas_bpn_tidak_menyimpan_kecamatan_dan_password_opsional_saat_ubah(): void
    {
        $kua = User::factory()->kua()->create();
        $hashLama = $kua->password;
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.pengguna.form', ['user' => $kua])
            ->assertSet('role', 'kua')
            ->set('role', 'bpn')
            ->call('simpan')
            ->assertHasNoErrors();

        $kua->refresh();
        $this->assertTrue($kua->hasRole('bpn'));
        $this->assertFalse($kua->hasRole('kua'));
        $this->assertNull($kua->kecamatan_id);
        $this->assertSame($hashLama, $kua->password);
    }

    public function test_mengubah_nazir_hanya_data_dasar_dan_role_tetap(): void
    {
        $nazir = User::factory()->nazir()->create();
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.pengguna.form', ['user' => $nazir])
            ->assertSet('modeNazir', true)
            ->set('name', 'Nazir Diubah')
            ->set('role', 'admin_bpn')
            ->set('username', 'nazir_jadi_admin')
            ->set('is_active', false)
            ->call('simpan')
            ->assertHasNoErrors();

        $nazir->refresh();
        $this->assertSame('Nazir Diubah', $nazir->name);
        $this->assertFalse($nazir->is_active);
        $this->assertTrue($nazir->isNazir());
        $this->assertFalse($nazir->hasRole('admin_bpn'));
        $this->assertNull($nazir->username);
    }

    public function test_toggle_aktif_dan_admin_tidak_bisa_menonaktifkan_diri(): void
    {
        $petugas = User::factory()->bpn()->create();
        $this->actingAs($this->admin);

        $daftar = Livewire::test('pages::admin.pengguna.index')->call('toggleAktif', $petugas->id);
        $this->assertFalse($petugas->fresh()->is_active);

        $daftar->call('toggleAktif', $petugas->id);
        $this->assertTrue($petugas->fresh()->is_active);

        $daftar->call('toggleAktif', $this->admin->id)->assertForbidden();
        $this->assertTrue($this->admin->fresh()->is_active);

        // Lewat form pun admin tidak bisa menonaktifkan diri atau mengubah role sendiri.
        Livewire::test('pages::admin.pengguna.form', ['user' => $this->admin])
            ->set('is_active', false)
            ->set('role', 'bpn')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);
        $this->assertTrue($this->admin->isAdmin());
    }

    public function test_kirim_tautan_reset_password(): void
    {
        Notification::fake();
        $petugas = User::factory()->bpn()->create();
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.pengguna.index')->call('kirimResetPassword', $petugas->id);

        Notification::assertSentTo($petugas, ResetPassword::class);
    }

    public function test_pembuatan_pengguna_tercatat_di_log_aktivitas(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('pages::admin.pengguna.form')
            ->set('name', 'Petugas Log')
            ->set('username', 'petugas_log')
            ->set('email', 'log@example.com')
            ->set('role', 'bpn')
            ->set('password', 'rahasia-123')
            ->set('password_confirmation', 'rahasia-123')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_log', ['log_name' => 'pengguna', 'causer_id' => $this->admin->id]);
    }
}
