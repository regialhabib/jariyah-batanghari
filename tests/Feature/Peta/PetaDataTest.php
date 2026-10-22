<?php

namespace Tests\Feature\Peta;

use App\Enums\JenisPenggunaan;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetaDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function kodeFitur(array $query = []): array
    {
        $respons = $this->getJson(route('peta.data', $query))->assertOk();

        return collect($respons->json('features'))->pluck('properties.kode')->sort()->values()->all();
    }

    public function test_hanya_tanah_terpublikasi_yang_dikirim_sebagai_geojson(): void
    {
        $publik = TanahWakaf::factory()->create(['latitude' => -1.7123456, 'longitude' => 103.2654321]);
        TanahWakaf::factory()->tanpaAiw()->create();

        $respons = $this->getJson(route('peta.data'))->assertOk();

        $respons->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.type', 'Feature')
            ->assertJsonPath('features.0.geometry.type', 'Point')
            ->assertJsonPath('features.0.geometry.coordinates', [103.2654321, -1.7123456])
            ->assertJsonPath('features.0.properties.kode', $publik->kode)
            ->assertJsonPath('features.0.properties.url', route('peta.show', $publik->kode))
            ->assertJsonPath('ringkasan', ['total' => 1, 'kecamatan' => 1, 'desa' => 1]);
    }

    public function test_properti_tidak_memuat_data_pribadi(): void
    {
        $nazir = User::factory()->nazir()->create(['phone' => '081234567890', 'email' => 'nazir.rahasia@example.com']);
        TanahWakaf::factory()->milik($nazir)->create(['wakif_nik' => '1504000011112222']);

        $respons = $this->getJson(route('peta.data'))->assertOk();
        $properti = $respons->json('features.0.properties');

        $this->assertSame($nazir->name, $properti['nazir']);
        foreach (['wakif_nik', 'wakif_nama', 'nik', 'phone', 'no_hp', 'email', 'nazir_id'] as $kunci) {
            $this->assertArrayNotHasKey($kunci, $properti);
        }

        $isi = $respons->getContent();
        $this->assertStringNotContainsString('1504000011112222', $isi);
        $this->assertStringNotContainsString('081234567890', $isi);
        $this->assertStringNotContainsString('nazir.rahasia', $isi);
    }

    public function test_filter_pencarian_nama_kode_dan_desa(): void
    {
        $desa = Desa::factory()->create(['nama' => 'Kilangan']);
        $a = TanahWakaf::factory()->create(['nama' => 'Masjid Al-Ikhlas']);
        $b = TanahWakaf::factory()->create(['nama' => 'Makam Umum', 'desa_id' => $desa->id]);
        TanahWakaf::factory()->create(['nama' => 'Sekolah Nurul Huda']);

        $this->assertSame([$a->kode], $this->kodeFitur(['q' => 'ikhlas']));
        $this->assertSame([$b->kode], $this->kodeFitur(['q' => 'kilangan']));
        $this->assertSame([$a->kode], $this->kodeFitur(['q' => $a->kode]));
        $this->assertSame([], $this->kodeFitur(['q' => '%']));
    }

    public function test_filter_kecamatan(): void
    {
        $kecamatan = Kecamatan::factory()->create();
        $di = TanahWakaf::factory()->create(['desa_id' => Desa::factory()->for($kecamatan)]);
        TanahWakaf::factory()->create();

        $this->assertSame([$di->kode], $this->kodeFitur(['kecamatan' => $kecamatan->id]));
        $this->getJson(route('peta.data', ['kecamatan' => $kecamatan->id]))
            ->assertJsonPath('ringkasan', ['total' => 1, 'kecamatan' => 1, 'desa' => 1]);
    }

    public function test_filter_jenis_penggunaan(): void
    {
        $masjid = TanahWakaf::factory()->create(['jenis_penggunaan' => JenisPenggunaan::Masjid]);
        TanahWakaf::factory()->create(['jenis_penggunaan' => JenisPenggunaan::Makam]);

        $this->assertSame([$masjid->kode], $this->kodeFitur(['jenis' => 'masjid']));
    }

    public function test_filter_status_sertifikat(): void
    {
        $sudah = TanahWakaf::factory()->bersertifikat()->create();
        $proses = TanahWakaf::factory()->create(['status_sertifikat' => StatusSertifikat::Proses]);
        TanahWakaf::factory()->create();

        $this->assertSame([$sudah->kode], $this->kodeFitur(['status' => 'sudah']));
        $this->assertSame([$proses->kode], $this->kodeFitur(['status' => 'proses']));
        $this->getJson(route('peta.data', ['status' => 'proses']))
            ->assertJsonPath('features.0.properties.warna', '#3B82F6');
    }

    public function test_nilai_filter_tidak_valid_diabaikan(): void
    {
        TanahWakaf::factory()->count(2)->create();

        $this->assertCount(2, $this->kodeFitur(['jenis' => 'pabrik', 'status' => 'x', 'kecamatan' => 'abc']));
    }
}
