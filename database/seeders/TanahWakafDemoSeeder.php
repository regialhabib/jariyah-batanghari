<?php

namespace Database\Seeders;

use App\Enums\JenisPengajuan;
use App\Enums\JenisPenggunaan;
use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Enums\StatusValidasiDokumen;
use App\Models\Desa;
use App\Models\Pengajuan;
use App\Models\Persyaratan;
use App\Models\TanahWakaf;
use App\Models\User;
use App\Services\PengajuanWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data demo: 125 bidang (90 bersertifikat, 35 belum) seperti angka di mockup Beranda,
 * termasuk 4 tanah milik Nazir demo "Ahmad": 3 seperti di mockup dashboard Nazir, ditambah
 * 1 pengajuan AIW yang menunggu KUA. Setiap pengajuan diberi dokumen contoh agar petugas
 * bisa langsung mencoba memeriksa dan memverifikasi.
 */
class TanahWakafDemoSeeder extends Seeder
{
    private const NAMA = ['Al-Ikhlas', 'Nurul Huda', 'Al-Falah', 'Baitul Makmur', 'Nurul Iman', 'At-Taqwa', 'Al-Hidayah', 'Darussalam', 'Al-Muhajirin', 'Baiturrahman', 'Al-Amin', 'Nurul Jannah'];

    public function run(): void
    {
        if (TanahWakaf::query()->exists()) {
            return;
        }

        $desas = Desa::query()->with('kecamatan')->get();
        $ahmad = User::query()->where('email', 'nazir@jariyah.test')->first();

        $this->tanahAhmad($ahmad, $desas);

        // 4 milik Ahmad: 2 bersertifikat, 1 dalam proses, 1 belum (AIW). Sisanya diatur agar total 90 sudah / 35 belum.
        for ($i = 0; $i < 121; $i++) {
            $desa = $desas->random();
            $jenis = fake()->randomElement([
                JenisPenggunaan::Masjid, JenisPenggunaan::Masjid, JenisPenggunaan::Mushola, JenisPenggunaan::Mushola,
                JenisPenggunaan::Pesantren, JenisPenggunaan::Sekolah, JenisPenggunaan::Makam, JenisPenggunaan::Lainnya,
            ]);
            $sudah = $i < 88;

            TanahWakaf::factory()
                ->when($sudah, fn ($f) => $f->bersertifikat())
                ->create([
                    'nama' => $jenis->label().' '.fake()->randomElement(self::NAMA),
                    'desa_id' => $desa->id,
                    'jenis_penggunaan' => $jenis,
                    'status_sertifikat' => $sudah ? StatusSertifikat::Sudah : StatusSertifikat::Belum,
                    ...$this->koordinat($desa),
                    'sumber' => 'impor',
                ]);
        }
    }

    /**
     * @param  Collection<int, Desa>  $desas
     */
    private function tanahAhmad(?User $ahmad, $desas): void
    {
        if (! $ahmad) {
            return;
        }

        $data = [
            ['Masjid Al-Ikhlas', 'Peninjauan', JenisPenggunaan::Masjid, 1500, StatusPengajuan::SertifikatTerbit],
            ['Mushola Nurul Huda', 'Sungai Rengas', JenisPenggunaan::Mushola, 450, StatusPengajuan::Diajukan],
            ['Pesantren Nurul Iman', 'Bajubang', JenisPenggunaan::Pesantren, 3200, StatusPengajuan::SertifikatTerbit],
            ['Masjid Al-Falah', 'Peninjauan', JenisPenggunaan::Masjid, 800, StatusPengajuan::Diajukan, JenisPengajuan::Aiw],
        ];

        foreach ($data as $baris) {
            [$nama, $namaDesa, $jenis, $luas, $status] = $baris;
            $desa = $desas->firstWhere('nama', $namaDesa) ?? $desas->first();
            $terbit = $status === StatusPengajuan::SertifikatTerbit;
            $jenisPengajuan = $baris[5] ?? JenisPengajuan::SertifikatBelum;
            $aiw = $jenisPengajuan === JenisPengajuan::Aiw;

            $tanah = TanahWakaf::factory()->milik($ahmad)
                ->when($terbit, fn ($f) => $f->bersertifikat())
                ->when($aiw, fn ($f) => $f->tanpaAiw())
                ->create([
                    'nama' => $nama,
                    'desa_id' => $desa->id,
                    'jenis_penggunaan' => $jenis,
                    'luas_m2' => $luas,
                    'status_sertifikat' => match (true) {
                        $terbit => StatusSertifikat::Sudah,
                        $aiw => StatusSertifikat::Belum,
                        default => StatusSertifikat::Proses,
                    },
                    ...$this->koordinat($desa),
                ]);

            $pengajuan = new Pengajuan([
                'jenis' => $jenisPengajuan,
                'tanah_wakaf_id' => $tanah->id,
                'nazir_id' => $ahmad->id,
                'status' => $status,
            ]);
            $pengajuan->nomor = Pengajuan::buatNomor();
            $pengajuan->diajukan_at = now()->subDays(fake()->numberBetween(10, 90));
            $pengajuan->selesai_at = $terbit ? now()->subDays(fake()->numberBetween(1, 9)) : null;
            $pengajuan->save();

            $pengajuan->riwayats()->create([
                'user_id' => $ahmad->id,
                'dari_status' => StatusPengajuan::Draf,
                'ke_status' => StatusPengajuan::Diajukan,
            ]);

            self::lampirkanDokumen($pengajuan, $terbit ? StatusValidasiDokumen::Valid : StatusValidasiDokumen::Menunggu);
        }
    }

    /**
     * Lampirkan PDF contoh untuk setiap persyaratan wajib. Tanpa dokumen, tombol Verifikasi/Sahkan AIW
     * tidak pernah aktif karena mensyaratkan semua dokumen persyaratan ditandai valid.
     */
    public static function lampirkanDokumen(Pengajuan $pengajuan, StatusValidasiDokumen $status): void
    {
        $persyaratans = Persyaratan::query()
            ->where('jenis_pengajuan', $pengajuan->jenis->value)
            ->where('wajib', true)
            ->orderBy('urutan')
            ->get();

        foreach ($persyaratans as $syarat) {
            $path = "pengajuan/{$pengajuan->id}/contoh-{$syarat->id}.pdf";
            $isi = self::pdfContoh($syarat->nama);
            Storage::disk(PengajuanWorkflow::DISK)->put($path, $isi);

            $pengajuan->dokumens()->create([
                'persyaratan_id' => $syarat->id,
                'kategori' => KategoriDokumen::Persyaratan,
                'path' => $path,
                'nama_asli' => Str::slug($syarat->nama).'.pdf',
                'mime' => 'application/pdf',
                'ukuran' => strlen($isi),
                'status_validasi' => $status,
            ]);
        }
    }

    /**
     * PDF satu halaman berisi judul dokumen, cukup untuk dibuka di peramban.
     */
    private static function pdfContoh(string $judul): string
    {
        $teks = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii('CONTOH DOKUMEN - '.$judul));
        $stream = "BT /F1 14 Tf 60 780 Td ({$teks}) Tj 0 -24 Td (Data demo Jariyah Batang Hari) Tj ET";
        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $i => $isiObjek) {
            $offset[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$isiObjek}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objek) + 1)."\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf."trailer\n<< /Size ".(count($objek) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    private function koordinat(Desa $desa): array
    {
        [$lat, $lng] = WilayahSeeder::PUSAT[$desa->kecamatan->nama] ?? [-1.70, 103.27];

        return [
            'latitude' => round($lat + fake()->randomFloat(4, -0.06, 0.06), 7),
            'longitude' => round($lng + fake()->randomFloat(4, -0.06, 0.06), 7),
        ];
    }
}
