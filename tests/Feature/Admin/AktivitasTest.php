<?php

namespace Tests\Feature\Admin;

use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Log Aktivitas (Fase 7): khusus Admin BPN.
 */
class AktivitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_admin_yang_bisa_melihat_log(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.aktivitas'))->assertOk();
        $this->actingAs(User::factory()->bpn()->create())->get(route('admin.aktivitas'))->assertForbidden();
    }

    public function test_menampilkan_perubahan_lama_ke_baru_dan_filter(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Pencatat']);
        $this->actingAs($admin);

        $tanah = TanahWakaf::factory()->create(['nama' => 'Masjid Lama', 'wakif_nik' => '1504000011112222']);
        $tanah->update(['nama' => 'Masjid Baru', 'wakif_nik' => '1504999988887777']);

        $komponen = Livewire::test('pages::admin.aktivitas')
            ->assertSee('Masjid Lama')
            ->assertSee('Masjid Baru')
            ->assertSee('Admin Pencatat')
            ->assertSee('(disembunyikan)')
            ->assertDontSee('1504999988887777');

        $komponen->set('logName', 'konten')->assertDontSee('Masjid Baru');
        $komponen->set('logName', 'tanah_wakaf')->assertSee('Masjid Baru');
        $komponen->set('cariPelaku', 'Orang Lain')->assertDontSee('Masjid Baru');
        $komponen->set('cariPelaku', 'Pencatat')->assertSee('Masjid Baru');
    }
}
