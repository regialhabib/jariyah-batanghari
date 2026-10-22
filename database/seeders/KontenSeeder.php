<?php

namespace Database\Seeders;

use App\Models\Artikel;
use App\Models\Faq;
use App\Models\PanduanLangkah;
use App\Models\Pengaturan;
use App\Models\Unduhan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Konten awal halaman publik. Semua teks bisa diubah Admin BPN lewat Kelola Konten.
 * Teks ini adalah DRAF yang perlu ditinjau pemilik proyek (docs/08-kebutuhan-data.md).
 */
class KontenSeeder extends Seeder
{
    public function run(): void
    {
        $this->artikel();
        $this->panduan();
        $this->faq();
        $this->unduhan();
        $this->pengaturan();
    }

    private function artikel(): void
    {
        $data = [
            ['Apa Itu Wakaf?', 'question-mark-circle', 'Wakaf adalah menahan harta yang dapat dimanfaatkan untuk kebaikan, baik dalam bentuk tanah, bangunan, maupun aset lainnya, demi kepentingan umat dan ibadah.',
                '<p>Menurut Undang-Undang Nomor 41 Tahun 2004 tentang Wakaf, wakaf adalah perbuatan hukum wakif untuk memisahkan dan/atau menyerahkan sebagian harta benda miliknya untuk dimanfaatkan selamanya atau untuk jangka waktu tertentu sesuai dengan kepentingannya guna keperluan ibadah dan/atau kesejahteraan umum menurut syariah.</p><p>Harta yang telah diwakafkan tidak boleh dijual, diwariskan, atau dihibahkan. Manfaatnya terus mengalir selama harta tersebut digunakan untuk kebaikan.</p>'],
            ['Wakif', 'user', 'Wakif adalah pihak yang mewakafkan harta atau benda miliknya untuk dimanfaatkan sesuai dengan ketentuan syariah.',
                '<p>Wakif dapat berupa perseorangan, organisasi, atau badan hukum. Wakif perseorangan harus dewasa, berakal sehat, tidak terhalang melakukan perbuatan hukum, dan merupakan pemilik sah harta benda yang diwakafkan.</p><p>Wakif menyatakan kehendaknya untuk berwakaf melalui ikrar wakaf di hadapan Pejabat Pembuat Akta Ikrar Wakaf (PPAIW) dengan disaksikan sekurang-kurangnya dua orang saksi.</p>'],
            ['Nazir', 'user-group', 'Nazir adalah pihak yang menerima, mengelola, dan mengembangkan harta wakaf sesuai dengan peruntukannya.',
                '<p>Nazir dapat berupa perseorangan, organisasi, atau badan hukum. Tugas Nazir meliputi:</p><ul><li>Melakukan pengadministrasian harta benda wakaf.</li><li>Mengelola dan mengembangkan harta benda wakaf sesuai tujuan, fungsi, dan peruntukannya.</li><li>Mengawasi dan melindungi harta benda wakaf.</li><li>Melaporkan pelaksanaan tugas kepada Badan Wakaf Indonesia.</li></ul><p>Di Jariyah Batang Hari, Nazir dapat mendaftarkan akun untuk mengajukan dan memantau pengurusan tanah wakaf.</p>'],
            ['Akta Ikrar Wakaf (AIW)', 'document-text', 'Akta Ikrar Wakaf adalah bukti tertulis yang menyatakan kehendak wakif untuk mewakafkan hartanya, yang dibuat di hadapan PPAIW di KUA Kecamatan.',
                '<p>Akta Ikrar Wakaf (AIW) dibuat oleh Pejabat Pembuat Akta Ikrar Wakaf (PPAIW), yaitu Kepala KUA Kecamatan setempat. AIW menjadi dasar untuk mendaftarkan tanah wakaf ke Kantor Pertanahan.</p><p>Melalui Jariyah Batang Hari, Nazir dapat mengajukan pembuatan AIW secara daring. Petugas KUA kecamatan akan memeriksa kelengkapan dokumen sebelum AIW disahkan.</p>'],
            ['Tanah Wakaf', 'home-modern', 'Tanah wakaf adalah tanah yang diwakafkan untuk kepentingan umum, sosial, keagamaan, atau pendidikan, dan dikelola oleh nazir sesuai peruntukannya.',
                '<p>Tanah wakaf banyak dimanfaatkan untuk masjid, mushola, pondok pesantren, sekolah/madrasah, dan makam. Agar terlindungi secara hukum, tanah wakaf perlu didaftarkan hingga memiliki sertifikat wakaf.</p>'],
            ['Sertifikat Tanah Wakaf', 'shield-check', 'Sertifikat tanah wakaf adalah bukti legalitas atas tanah wakaf yang diterbitkan oleh Badan Pertanahan Nasional (BPN) sebagai jaminan kepastian hukum.',
                '<p>Sertifikat wakaf diterbitkan oleh Kantor Pertanahan setelah AIW disahkan dan berkas permohonan dinyatakan lengkap serta valid. Proses ini mencakup pemeriksaan dokumen, pengukuran, dan pemasangan tanda batas bidang tanah.</p>'],
            ['Kepastian Hukum Wakaf', 'scale', 'Kepastian hukum wakaf penting untuk melindungi aset wakaf dari sengketa dan memastikan manfaatnya dapat diterima secara berkelanjutan oleh masyarakat.',
                '<p>Tanah wakaf yang belum bersertifikat rentan terhadap sengketa, klaim ahli waris, maupun penyalahgunaan. Dengan sertifikat wakaf, status tanah tercatat resmi di Kantor Pertanahan sehingga manfaatnya terjaga untuk generasi mendatang.</p>'],
        ];

        foreach ($data as $i => [$judul, $ikon, $ringkasan, $isi]) {
            Artikel::query()->updateOrCreate(['slug' => Str::slug($judul)], [
                'judul' => $judul, 'ikon' => $ikon, 'ringkasan' => $ringkasan, 'isi' => $isi, 'urutan' => $i + 1, 'is_published' => true,
            ]);
        }
    }

    private function panduan(): void
    {
        $data = [
            ['Persiapan Dokumen', 'Pastikan semua dokumen persyaratan sudah lengkap sesuai jenis pengajuan.'],
            ['Pendaftaran Akun', 'Daftar akun di website Jariyah Batang Hari untuk mengakses sistem.'],
            ['Pengajuan Data Wakaf', 'Isi formulir pengajuan dan unggah dokumen melalui akun Anda.'],
            ['Verifikasi dan Validasi', 'Data akan diverifikasi oleh petugas terkait (BPN, Kemenag/KUA, dan lain-lain).'],
            ['Penerbitan Sertifikat', 'Setelah dinyatakan valid, proses penerbitan sertifikat tanah wakaf dapat dilakukan.'],
        ];

        foreach ($data as $i => [$judul, $deskripsi]) {
            PanduanLangkah::query()->updateOrCreate(['urutan' => $i + 1], ['judul' => $judul, 'deskripsi' => $deskripsi]);
        }
    }

    private function faq(): void
    {
        $data = [
            ['Siapa yang bisa mendaftar akun di Jariyah Batang Hari?', 'Pendaftaran akun terbuka untuk Nazir, yaitu pihak yang mengelola tanah wakaf. Akun petugas BPN dan KUA dibuat oleh Admin Kantor Pertanahan.'],
            ['Apakah layanan ini dipungut biaya?', 'Penggunaan website Jariyah Batang Hari tidak dipungut biaya. Untuk biaya proses sertifikasi, silakan konfirmasi ke Kantor Pertanahan Kabupaten Batang Hari.'],
            ['Apa perbedaan pengajuan AIW dan pengajuan sertifikat?', 'Pengajuan AIW diperiksa oleh KUA Kecamatan untuk menerbitkan Akta Ikrar Wakaf. Setelah AIW disahkan, Nazir dapat mengajukan sertifikat wakaf yang diverifikasi oleh Kantor Pertanahan (BPN).'],
            ['Berapa lama proses verifikasi?', 'Lama proses bergantung pada kelengkapan dokumen. Anda dapat memantau status pengajuan kapan saja melalui dashboard Nazir.'],
            ['Bagaimana jika dokumen saya dinyatakan perlu perbaikan?', 'Anda akan menerima notifikasi beserta catatan petugas. Ganti dokumen yang ditandai tidak valid, lalu kirim ulang pengajuan.'],
            ['Format file apa yang bisa diunggah?', 'Dokumen dapat diunggah dalam format PDF, JPG, atau PNG dengan ukuran maksimal 5 MB per file.'],
            ['Bagaimana cara mendapatkan titik koordinat tanah?', 'Gunakan aplikasi kamera GPS (GPS Camera) di ponsel saat berada di lokasi tanah, atau tandai lokasi langsung pada peta di formulir pengajuan.'],
            ['Apakah data pribadi saya aman?', 'Data pribadi seperti NIK dan nomor HP tidak ditampilkan di halaman publik dan hanya dapat diakses oleh petugas yang berwenang.'],
        ];

        foreach ($data as $i => [$pertanyaan, $jawaban]) {
            Faq::query()->updateOrCreate(['pertanyaan' => $pertanyaan], ['jawaban' => $jawaban, 'urutan' => $i + 1, 'is_published' => true]);
        }
    }

    private function unduhan(): void
    {
        $data = ['Formulir Permohonan', 'Contoh Akta Ikrar Wakaf', 'Contoh Surat Kuasa', 'Contoh Surat Pernyataan', 'Brosur Wakaf'];

        foreach ($data as $i => $judul) {
            Unduhan::query()->updateOrCreate(['judul' => $judul], ['keterangan' => 'Unduh di sini', 'urutan' => $i + 1]);
        }
    }

    private function pengaturan(): void
    {
        $data = [
            'kontak' => [
                'alamat' => 'Kantor Pertanahan Kabupaten Batang Hari, Jl. Jend. Sudirman, Muara Bulian, Kabupaten Batang Hari, Jambi',
                'telepon' => '(0743) 21045',
                'email' => 'kab-batanghari@atrbpn.go.id',
                'jam_operasional' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            'beranda' => [
                'hero_judul' => 'JARIYAH BATANG HARI',
                'hero_subjudul' => 'Sistem Informasi Pengelolaan dan Monitoring Tanah Wakaf Kabupaten Batang Hari',
                'hero_deskripsi' => 'Menyediakan informasi, pengelolaan data, serta monitoring tanah wakaf secara terintegrasi dalam mendukung kepastian hukum dan pengelolaan aset wakaf di Kabupaten Batang Hari.',
            ],
            'tentang' => [
                'tentang_deskripsi' => 'Jariyah Batang Hari adalah sistem informasi berbasis website yang dikembangkan oleh Kantor Pertanahan Kabupaten Batang Hari untuk mendukung pengelolaan dan monitoring tanah wakaf secara terintegrasi, transparan, dan akuntabel.',
                'tentang_tujuan' => 'Mempermudah pengelolaan data tanah wakaf dan memperkuat kepastian hukum aset wakaf.',
                'tentang_manfaat' => json_encode(['Akses informasi lebih mudah', 'Data lebih akurat dan terintegrasi', 'Mempercepat proses verifikasi', 'Mendukung pengelolaan wakaf yang berkelanjutan']),
                'tentang_instansi' => json_encode(['Kantor Pertanahan (BPN)', 'Kemenag / KUA', 'Pemerintah Kecamatan', 'Nazir / Pengguna']),
            ],
            'sosial' => [
                'sosial_facebook' => '',
                'sosial_instagram' => '',
                'sosial_youtube' => '',
            ],
        ];

        foreach ($data as $grup => $pasangan) {
            foreach ($pasangan as $kunci => $nilai) {
                Pengaturan::set($kunci, $nilai, $grup);
            }
        }
    }
}
