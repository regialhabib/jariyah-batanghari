<?php

namespace Database\Seeders;

use App\Enums\JenisPengajuan;
use App\Models\Persyaratan;
use Illuminate\Database\Seeder;

/**
 * Persyaratan sesuai halaman Persyaratan pada rancangan (docs/01-rancangan-halaman.md, H4).
 */
class PersyaratanSeeder extends Seeder
{
    /**
     * @var array<string, list<array{0: string, 1: bool}>> [nama, wajib]
     */
    private const DATA = [
        'aiw' => [
            ['Fotokopi KTP/KK Wakif dan Nazir', true],
            ['Fotokopi KTP saksi', true],
            ['Surat pernyataan penguasaan fisik bidang tanah', true],
            ['Surat asal tanah (jual beli/hibah/waris/sertifikat, dll)', true],
            ['Surat pernyataan tidak dalam sengketa', true],
            ['Surat pernyataan bersedia menjadi Nazir', true],
            ['Surat pernyataan bersedia diaudit bagi Nazir', true],
            ['Foto koordinat/geotagging (GPS Camera)', true],
        ],
        'sertifikat_bersertifikat' => [
            ['Formulir permohonan', true],
            ['Surat kuasa apabila dikuasakan', false],
            ['Fotokopi KTP/KK Wakif, Nazir, dan kuasa (jika dikuasakan)', true],
            ['Akta Ikrar Wakaf dari KUA Kecamatan', true],
            ['Sertifikat asli', true],
            ['Surat persetujuan pemasangan tanda batas', true],
            ['Surat pernyataan tidak dalam sengketa', true],
            ['Foto koordinat/geotagging', true],
        ],
        'sertifikat_belum' => [
            ['Formulir permohonan', true],
            ['Surat kuasa apabila dikuasakan', false],
            ['Fotokopi KTP/KK Wakif, Nazir, dan kuasa (jika dikuasakan)', true],
            ['Akta Ikrar Wakaf dari KUA Kecamatan', true],
            ['Bukti kepemilikan tanah/alas hak', true],
            ['Surat persetujuan pemasangan tanda batas', true],
            ['Surat pernyataan tidak dalam sengketa', true],
            ['Foto koordinat/geotagging', true],
        ],
    ];

    public function run(): void
    {
        foreach (self::DATA as $jenis => $daftar) {
            foreach ($daftar as $i => [$nama, $wajib]) {
                Persyaratan::query()->updateOrCreate(
                    ['jenis_pengajuan' => JenisPengajuan::from($jenis), 'nama' => $nama],
                    ['wajib' => $wajib, 'urutan' => $i + 1],
                );
            }
        }
    }
}
