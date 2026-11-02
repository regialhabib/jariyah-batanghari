<?php

namespace Tests\Feature\Admin;

use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_migrasi_memindahkan_log_lama_dari_attribute_changes_ke_properties(): void
    {
        Schema::table('activity_log', fn (Blueprint $table) => $table->json('attribute_changes')->nullable());

        $id = DB::table('activity_log')->insertGetId([
            'log_name' => 'tanah_wakaf',
            'description' => 'updated',
            'attribute_changes' => json_encode(['attributes' => ['nama' => 'Masjid Baru'], 'old' => ['nama' => 'Masjid Lama']]),
            'properties' => json_encode(['catatan' => 'Tetap ada']),
        ]);

        (require database_path('migrations/2026_11_02_101559_sesuaikan_activity_log_untuk_activitylog_v4.php'))->up();

        $this->assertFalse(Schema::hasColumn('activity_log', 'attribute_changes'));
        $this->assertSame([
            'catatan' => 'Tetap ada',
            'attributes' => ['nama' => 'Masjid Baru'],
            'old' => ['nama' => 'Masjid Lama'],
        ], json_decode(DB::table('activity_log')->where('id', $id)->value('properties'), true));
    }
}
