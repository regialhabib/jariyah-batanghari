<?php

namespace App\Services;

use App\Enums\JenisPengajuan;
use App\Enums\KategoriDokumen;
use App\Enums\StatusPengajuan;
use App\Enums\StatusSertifikat;
use App\Enums\StatusValidasiDokumen;
use App\Models\Desa;
use App\Models\Pengajuan;
use App\Models\PengajuanDokumen;
use App\Models\Persyaratan;
use App\Models\TanahWakaf;
use App\Models\User;
use App\Notifications\PengajuanMasuk;
use App\Notifications\StatusPengajuanBerubah;
use App\Rules\NomorBelumDipakai;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Satu pintu untuk semua perubahan status pengajuan (lihat docs/03-alur-pengajuan.md).
 *
 * Setiap method memeriksa hak akses (Policy) dan keabsahan transisi, menjalankan efek samping
 * pada tanah wakaf di dalam transaksi, mencatat riwayat, lalu memberi notifikasi ke Nazir.
 * Kesalahan dilempar sebagai ValidationException agar langsung tampil di form Livewire.
 */
class PengajuanWorkflow
{
    public const DISK = 'local';

    /**
     * Membuat draf pengajuan. Untuk jenis AIW, data tanah dibuat bersamaan (belum dipublikasikan).
     *
     * @param  array<string, mixed>  $dataTanah  wajib untuk jenis AIW
     */
    public function buatDraf(User $nazir, JenisPengajuan $jenis, ?TanahWakaf $tanah = null, array $dataTanah = []): Pengajuan
    {
        Gate::forUser($nazir)->authorize('create', Pengajuan::class);

        return DB::transaction(function () use ($nazir, $jenis, $tanah, $dataTanah) {
            if ($jenis === JenisPengajuan::Aiw) {
                $tanah = TanahWakaf::create([
                    ...$dataTanah,
                    'nazir_id' => $nazir->id,
                    'status_sertifikat' => StatusSertifikat::Belum,
                    'is_published' => false,
                    'sumber' => 'pengajuan',
                ]);
            } else {
                $this->pastikanBisaAjukanSertifikat($nazir, $tanah);
            }

            return Pengajuan::create([
                'jenis' => $jenis,
                'tanah_wakaf_id' => $tanah->id,
                'nazir_id' => $nazir->id,
                'status' => StatusPengajuan::Draf,
            ]);
        });
    }

    /**
     * Mengubah data tanah milik draf/perbaikan pengajuan AIW. Kode tanah ikut menyesuaikan
     * bila kecamatan berubah, selama tanah belum dipublikasikan dan belum punya nomor AIW.
     *
     * @param  array<string, mixed>  $dataTanah
     */
    public function ubahDataTanah(Pengajuan $pengajuan, User $nazir, array $dataTanah): TanahWakaf
    {
        Gate::forUser($nazir)->authorize('update', $pengajuan);

        if ($pengajuan->jenis !== JenisPengajuan::Aiw) {
            throw ValidationException::withMessages(['status' => 'Data tanah hanya bisa diubah pada pengajuan AIW.']);
        }

        $tanah = $pengajuan->tanahWakaf()->with('desa')->firstOrFail();
        $desaBaru = (int) $dataTanah['desa_id'];

        if ($desaBaru !== $tanah->desa_id && ! $tanah->is_published && blank($tanah->nomor_aiw)
            && Desa::find($desaBaru)?->kecamatan_id !== $tanah->desa?->kecamatan_id) {
            $tanah->kode = TanahWakaf::buatKode($desaBaru);
        }

        $tanah->fill($dataTanah)->save();

        return $tanah;
    }

    /**
     * Menghapus draf beserta dokumennya. Tanah yang lahir dari draf AIW ini (belum disahkan,
     * belum dipublikasikan, tanpa pengajuan lain) ikut dihapus.
     */
    public function hapusDraf(Pengajuan $pengajuan, User $nazir): void
    {
        Gate::forUser($nazir)->authorize('delete', $pengajuan);

        $paths = $pengajuan->dokumens()->pluck('path')->all();

        DB::transaction(function () use ($pengajuan) {
            $tanah = $pengajuan->tanahWakaf;
            $pengajuan->delete();

            if ($pengajuan->jenis === JenisPengajuan::Aiw && $tanah
                && $tanah->sumber === 'pengajuan' && blank($tanah->nomor_aiw) && ! $tanah->is_published
                && ! $tanah->pengajuans()->exists()) {
                $tanah->delete();
            }
        });

        Storage::disk(self::DISK)->delete($paths);
    }

    /**
     * Mengunggah (atau mengganti) dokumen persyaratan. Dokumen pengganti kembali berstatus menunggu.
     */
    public function unggahDokumen(Pengajuan $pengajuan, Persyaratan $persyaratan, UploadedFile $file, User $nazir): PengajuanDokumen
    {
        Gate::forUser($nazir)->authorize('update', $pengajuan);

        if ($persyaratan->jenis_pengajuan !== $pengajuan->jenis) {
            throw ValidationException::withMessages(['dokumen' => 'Persyaratan tidak sesuai dengan jenis pengajuan.']);
        }

        $lama = $pengajuan->dokumens()->where('persyaratan_id', $persyaratan->id)->first();

        if ($lama && $pengajuan->status === StatusPengajuan::PerluPerbaikan
            && $lama->status_validasi === StatusValidasiDokumen::Valid) {
            throw ValidationException::withMessages(['dokumen' => 'Dokumen yang sudah dinyatakan valid tidak perlu diganti.']);
        }

        $baru = $this->simpanFile($pengajuan, $file, KategoriDokumen::Persyaratan, $persyaratan);

        if ($lama) {
            Storage::disk(self::DISK)->delete($lama->path);
            $lama->delete();
        }

        return $baru;
    }

    /**
     * Nazir mengirim (atau mengirim ulang) pengajuan.
     */
    public function kirim(Pengajuan $pengajuan, User $nazir): void
    {
        Gate::forUser($nazir)->authorize('kirim', $pengajuan);

        $kurang = $pengajuan->persyaratanBelumLengkap();
        if ($kurang->isNotEmpty()) {
            throw ValidationException::withMessages([
                'dokumen' => 'Dokumen wajib belum lengkap: '.$kurang->pluck('nama')->implode(', ').'.',
            ]);
        }

        if ($pengajuan->status === StatusPengajuan::PerluPerbaikan && $pengajuan->adaDokumenTidakValid()) {
            throw ValidationException::withMessages([
                'dokumen' => 'Masih ada dokumen yang ditandai tidak valid. Ganti dokumen tersebut sebelum mengirim ulang.',
            ]);
        }

        if ($pengajuan->status === StatusPengajuan::Draf) {
            $this->pastikanTidakAdaPengajuanAktifLain($pengajuan);
        }

        DB::transaction(function () use ($pengajuan, $nazir) {
            $pengajuan->nomor ??= Pengajuan::buatNomor();
            $pengajuan->diajukan_at ??= now();

            if ($pengajuan->jenis->isSertifikat()) {
                $pengajuan->tanahWakaf->update(['status_sertifikat' => StatusSertifikat::Proses]);
            }

            $this->transisi($pengajuan, StatusPengajuan::Diajukan, $nazir, null, beriTahuNazir: false);
        });

        Notification::send($this->pemeriksaUntuk($pengajuan), new PengajuanMasuk($pengajuan));
    }

    /**
     * Petugas yang berwenang memeriksa: KUA di kecamatan lokasi tanah, atau semua petugas BPN.
     *
     * @return Collection<int, User>
     */
    public function pemeriksaUntuk(Pengajuan $pengajuan): Collection
    {
        $roles = array_map(fn ($role) => $role->value, $pengajuan->jenis->pemeriksa());

        return User::query()
            ->role($roles)
            ->where('is_active', true)
            ->when($pengajuan->jenis === JenisPengajuan::Aiw, fn ($q) => $q->where('kecamatan_id', $pengajuan->tanahWakaf->desa->kecamatan_id))
            ->get();
    }

    /**
     * Pemeriksa menandai satu dokumen valid/tidak valid.
     */
    public function validasiDokumen(PengajuanDokumen $dokumen, User $pemeriksa, StatusValidasiDokumen $status, ?string $catatan = null): void
    {
        $pengajuan = $dokumen->pengajuan;
        Gate::forUser($pemeriksa)->authorize('periksa', $pengajuan);

        if ($pengajuan->status !== StatusPengajuan::Diajukan) {
            throw ValidationException::withMessages(['dokumen' => 'Dokumen hanya bisa divalidasi saat pengajuan berstatus Diajukan.']);
        }

        if ($status === StatusValidasiDokumen::TidakValid && blank($catatan)) {
            throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi untuk dokumen yang tidak valid.']);
        }

        $dokumen->update(['status_validasi' => $status, 'catatan' => $catatan]);
    }

    public function mintaPerbaikan(Pengajuan $pengajuan, User $pemeriksa, string $catatan): void
    {
        $this->wajibCatatan($catatan);

        DB::transaction(fn () => $this->transisiOlehPemeriksa($pengajuan, StatusPengajuan::PerluPerbaikan, $pemeriksa, $catatan));
    }

    public function tolak(Pengajuan $pengajuan, User $pemeriksa, string $alasan): void
    {
        $this->wajibCatatan($alasan);

        DB::transaction(function () use ($pengajuan, $pemeriksa, $alasan) {
            if ($pengajuan->jenis->isSertifikat()) {
                $pengajuan->tanahWakaf->update(['status_sertifikat' => StatusSertifikat::Belum]);
            }

            $this->transisiOlehPemeriksa($pengajuan, StatusPengajuan::Ditolak, $pemeriksa, $alasan);
        });
    }

    /**
     * KUA mengesahkan AIW: tanah dipublikasikan dengan nomor & tanggal AIW.
     */
    public function sahkanAiw(Pengajuan $pengajuan, User $kua, string $nomorAiw, DateTimeInterface $tanggalAiw, ?UploadedFile $scan = null, ?string $catatan = null): void
    {
        $this->pastikanSemuaDokumenValid($pengajuan);
        $nomorAiw = $this->pastikanNomorBelumDipakai($pengajuan->tanahWakaf, 'nomor_aiw', $nomorAiw, 'nomor AIW');

        DB::transaction(function () use ($pengajuan, $kua, $nomorAiw, $tanggalAiw, $scan, $catatan) {
            $pengajuan->tanahWakaf->update([
                'nomor_aiw' => $nomorAiw,
                'tanggal_aiw' => $tanggalAiw,
                'is_published' => true,
            ]);

            if ($scan) {
                $this->simpanFile($pengajuan, $scan, KategoriDokumen::HasilAiw);
            }

            $this->transisiOlehPemeriksa($pengajuan, StatusPengajuan::AiwDisahkan, $kua, $catatan);
        });
    }

    /**
     * BPN menyatakan berkas sertifikat valid.
     */
    public function verifikasi(Pengajuan $pengajuan, User $bpn, ?string $catatan = null): void
    {
        $this->pastikanSemuaDokumenValid($pengajuan);

        DB::transaction(fn () => $this->transisiOlehPemeriksa($pengajuan, StatusPengajuan::Terverifikasi, $bpn, $catatan));
    }

    /**
     * BPN mencatat sertifikat wakaf yang sudah terbit.
     */
    public function terbitkanSertifikat(Pengajuan $pengajuan, User $bpn, string $nomorSertifikat, DateTimeInterface $tanggal, ?UploadedFile $scan = null, ?string $catatan = null): void
    {
        $nomorSertifikat = $this->pastikanNomorBelumDipakai($pengajuan->tanahWakaf, 'nomor_sertifikat', $nomorSertifikat, 'nomor sertifikat');

        DB::transaction(function () use ($pengajuan, $bpn, $nomorSertifikat, $tanggal, $scan, $catatan) {
            $pengajuan->tanahWakaf->update([
                'status_sertifikat' => StatusSertifikat::Sudah,
                'nomor_sertifikat' => $nomorSertifikat,
                'tanggal_sertifikat' => $tanggal,
                'is_published' => true,
            ]);

            if ($scan) {
                $this->simpanFile($pengajuan, $scan, KategoriDokumen::HasilSertifikat);
            }

            $this->transisiOlehPemeriksa($pengajuan, StatusPengajuan::SertifikatTerbit, $bpn, $catatan);
        });
    }

    private function transisiOlehPemeriksa(Pengajuan $pengajuan, StatusPengajuan $ke, User $pemeriksa, ?string $catatan): void
    {
        Gate::forUser($pemeriksa)->authorize('periksa', $pengajuan);

        $pengajuan->pemeriksa_id = $pemeriksa->id;
        $pengajuan->catatan_pemeriksa = $catatan;

        $this->transisi($pengajuan, $ke, $pemeriksa, $catatan);
    }

    private function transisi(Pengajuan $pengajuan, StatusPengajuan $ke, User $pelaku, ?string $catatan, bool $beriTahuNazir = true): void
    {
        $dari = $pengajuan->status;

        if (! $dari->bisaKe($ke, $pengajuan->jenis)) {
            throw ValidationException::withMessages([
                'status' => "Status tidak bisa diubah dari {$dari->label()} ke {$ke->label()}.",
            ]);
        }

        $pengajuan->status = $ke;
        if ($ke->isFinal()) {
            $pengajuan->selesai_at = now();
        }
        $pengajuan->save();

        $pengajuan->riwayats()->create([
            'user_id' => $pelaku->id,
            'dari_status' => $dari,
            'ke_status' => $ke,
            'catatan' => $catatan,
        ]);

        activity('pengajuan')
            ->performedOn($pengajuan)
            ->causedBy($pelaku)
            ->withProperties(['dari' => $dari->value, 'ke' => $ke->value, 'catatan' => $catatan])
            ->log("Status pengajuan {$pengajuan->nomor}: {$dari->label()} → {$ke->label()}");

        if ($beriTahuNazir) {
            $pengajuan->nazir->notify(new StatusPengajuanBerubah($pengajuan, $ke, $catatan));
        }
    }

    private function simpanFile(Pengajuan $pengajuan, UploadedFile $file, KategoriDokumen $kategori, ?Persyaratan $persyaratan = null): PengajuanDokumen
    {
        $path = $file->store("pengajuan/{$pengajuan->id}", self::DISK);

        return $pengajuan->dokumens()->create([
            'persyaratan_id' => $persyaratan?->id,
            'kategori' => $kategori,
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'ukuran' => $file->getSize(),
            'status_validasi' => $kategori === KategoriDokumen::Persyaratan
                ? StatusValidasiDokumen::Menunggu
                : StatusValidasiDokumen::Valid,
        ]);
    }

    private function pastikanBisaAjukanSertifikat(User $nazir, ?TanahWakaf $tanah): void
    {
        if (! $tanah || $tanah->nazir_id !== $nazir->id) {
            throw ValidationException::withMessages(['tanah_wakaf_id' => 'Pilih tanah wakaf milik Anda.']);
        }

        if (blank($tanah->nomor_aiw)) {
            throw ValidationException::withMessages(['tanah_wakaf_id' => 'Tanah ini belum memiliki Akta Ikrar Wakaf yang disahkan.']);
        }

        if ($tanah->status_sertifikat === StatusSertifikat::Sudah) {
            throw ValidationException::withMessages(['tanah_wakaf_id' => 'Tanah ini sudah bersertifikat wakaf.']);
        }
    }

    private function pastikanTidakAdaPengajuanAktifLain(Pengajuan $pengajuan): void
    {
        $aktifLain = Pengajuan::query()
            ->where('tanah_wakaf_id', $pengajuan->tanah_wakaf_id)
            ->where('jenis', $pengajuan->jenis->value)
            ->whereKeyNot($pengajuan->id)
            ->whereIn('status', [StatusPengajuan::Diajukan->value, StatusPengajuan::PerluPerbaikan->value, StatusPengajuan::Terverifikasi->value])
            ->exists();

        if ($aktifLain) {
            throw ValidationException::withMessages(['status' => 'Tanah ini masih punya pengajuan yang sedang diproses.']);
        }
    }

    /**
     * Tolak nomor AIW/sertifikat yang sudah dipakai tanah lain. Mengembalikan nomor yang sudah dirapikan.
     *
     * @param  'nomor_aiw'|'nomor_sertifikat'  $kolom
     */
    private function pastikanNomorBelumDipakai(TanahWakaf $tanah, string $kolom, string $nomor, string $label): string
    {
        Validator::make(
            [$kolom => $nomor],
            [$kolom => ['required', 'string', 'max:100', new NomorBelumDipakai($kolom, $tanah->id)]],
            [],
            [$kolom => $label],
        )->validate();

        return trim($nomor);
    }

    private function pastikanSemuaDokumenValid(Pengajuan $pengajuan): void
    {
        $belum = $pengajuan->dokumens()
            ->where('kategori', KategoriDokumen::Persyaratan->value)
            ->where('status_validasi', '!=', StatusValidasiDokumen::Valid->value)
            ->exists();

        if ($belum) {
            throw ValidationException::withMessages([
                'dokumen' => 'Semua dokumen persyaratan harus dinyatakan valid terlebih dahulu.',
            ]);
        }
    }

    private function wajibCatatan(?string $catatan): void
    {
        if (blank($catatan)) {
            throw ValidationException::withMessages(['catatan' => 'Catatan/alasan wajib diisi.']);
        }
    }
}
