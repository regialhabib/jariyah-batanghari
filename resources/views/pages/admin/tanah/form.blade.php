<?php

use App\Enums\JenisPenggunaan;
use App\Enums\Role;
use App\Enums\StatusSertifikat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Form Data Wakaf')] class extends Component {
    use WithFileUploads;

    public ?TanahWakaf $tanah = null;

    public string $nama = '';

    public string $jenis_penggunaan = '';

    /** 'akun' = pilih akun Nazir terdaftar, 'teks' = isi nama Nazir bebas. */
    public string $sumber_nazir = 'teks';

    public string $nazir_id = '';

    public string $nama_nazir = '';

    public string $wakif_nama = '';

    public string $wakif_nik = '';

    public string $kecamatan_id = '';

    public string $desa_id = '';

    public string $alamat = '';

    public string $luas_m2 = '';

    public string $status_sertifikat = 'belum';

    public string $nomor_aiw = '';

    public string $tanggal_aiw = '';

    public string $nomor_sertifikat = '';

    public string $tanggal_sertifikat = '';

    public bool $is_published = true;

    public $foto = null;

    public bool $hapus_foto = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?array $batas_geojson = null;

    public function mount(?TanahWakaf $tanah = null): void
    {
        $this->tanah = $tanah?->exists ? $tanah : null;
        $this->tanah ? $this->authorize('update', $this->tanah) : $this->authorize('create', TanahWakaf::class);

        if ($t = $this->tanah) {
            $t->loadMissing('desa');
            $this->nama = $t->nama;
            $this->jenis_penggunaan = $t->jenis_penggunaan->value;
            $this->sumber_nazir = $t->nazir_id ? 'akun' : 'teks';
            $this->nazir_id = (string) ($t->nazir_id ?? '');
            $this->nama_nazir = (string) $t->nama_nazir;
            $this->wakif_nama = $t->wakif_nama;
            $this->wakif_nik = (string) $t->wakif_nik;
            $this->kecamatan_id = (string) $t->desa?->kecamatan_id;
            $this->desa_id = (string) $t->desa_id;
            $this->alamat = (string) $t->alamat;
            $this->luas_m2 = (string) (float) $t->luas_m2;
            $this->status_sertifikat = $t->status_sertifikat->value;
            $this->nomor_aiw = (string) $t->nomor_aiw;
            $this->tanggal_aiw = (string) $t->tanggal_aiw?->format('Y-m-d');
            $this->nomor_sertifikat = (string) $t->nomor_sertifikat;
            $this->tanggal_sertifikat = (string) $t->tanggal_sertifikat?->format('Y-m-d');
            $this->is_published = $t->is_published;
            $this->latitude = (float) $t->latitude;
            $this->longitude = (float) $t->longitude;
            $this->batas_geojson = $t->batas_geojson;
        }
    }

    public function updatedKecamatanId(): void
    {
        $this->desa_id = '';
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarKecamatan(): array
    {
        return Kecamatan::query()->orderBy('nama')->pluck('nama', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarDesa(): array
    {
        if (! (int) $this->kecamatan_id) {
            return [];
        }

        return Desa::query()->where('kecamatan_id', (int) $this->kecamatan_id)->orderBy('nama')->pluck('nama', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function daftarNazir(): array
    {
        return User::query()->role(Role::Nazir->value)->where('is_active', true)
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jenis_penggunaan' => ['required', Rule::enum(JenisPenggunaan::class)],
            'sumber_nazir' => ['required', 'in:akun,teks'],
            'nazir_id' => ['nullable', 'required_if:sumber_nazir,akun', Rule::exists('users', 'id')->where('is_active', true),
                function (string $attr, mixed $value, \Closure $fail) {
                    if ($this->sumber_nazir === 'akun' && filled($value) && ! User::query()->whereKey($value)->role(Role::Nazir->value)->exists()) {
                        $fail('Akun yang dipilih bukan Nazir aktif.');
                    }
                }],
            'nama_nazir' => ['nullable', 'string', 'max:255'],
            'wakif_nama' => ['required', 'string', 'max:255'],
            'wakif_nik' => ['nullable', 'digits:16'],
            'kecamatan_id' => ['required', 'integer', 'exists:kecamatans,id'],
            'desa_id' => ['required', 'integer', Rule::exists('desas', 'id')->where('kecamatan_id', (int) $this->kecamatan_id)],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'luas_m2' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'status_sertifikat' => ['required', Rule::enum(StatusSertifikat::class)],
            'nomor_aiw' => ['nullable', 'string', 'max:100'],
            'tanggal_aiw' => ['nullable', 'date', 'before_or_equal:today'],
            'nomor_sertifikat' => ['nullable', 'required_if:status_sertifikat,sudah', 'string', 'max:100'],
            'tanggal_sertifikat' => ['nullable', 'date', 'before_or_equal:today'],
            'is_published' => ['boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'batas_geojson' => ['nullable', 'array'],
            'batas_geojson.type' => ['required_with:batas_geojson', 'in:Polygon,MultiPolygon'],
            'batas_geojson.coordinates' => ['required_with:batas_geojson', 'array'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nazir_id.required_if' => 'Pilih akun Nazir.',
            'nomor_sertifikat.required_if' => 'Nomor sertifikat wajib diisi jika tanah sudah bersertifikat.',
            'latitude.required' => 'Tentukan titik lokasi pada peta.',
            'longitude.required' => 'Tentukan titik lokasi pada peta.',
            'wakif_nik.digits' => 'NIK wakif harus 16 digit angka.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nama' => 'nama tanah wakaf',
            'jenis_penggunaan' => 'jenis penggunaan',
            'nazir_id' => 'Nazir',
            'nama_nazir' => 'nama Nazir',
            'wakif_nama' => 'nama wakif',
            'wakif_nik' => 'NIK wakif',
            'kecamatan_id' => 'kecamatan',
            'desa_id' => 'desa/kelurahan',
            'alamat' => 'alamat',
            'luas_m2' => 'luas',
            'status_sertifikat' => 'status sertifikat',
            'nomor_aiw' => 'nomor AIW',
            'tanggal_aiw' => 'tanggal AIW',
            'nomor_sertifikat' => 'nomor sertifikat',
            'tanggal_sertifikat' => 'tanggal sertifikat',
            'foto' => 'foto',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'batas_geojson' => 'batas bidang',
        ];
    }

    public function simpan(): void
    {
        $this->tanah ? $this->authorize('update', $this->tanah) : $this->authorize('create', TanahWakaf::class);

        $v = $this->validate();

        $pakaiAkun = $v['sumber_nazir'] === 'akun';
        $data = [
            'nama' => $v['nama'],
            'jenis_penggunaan' => $v['jenis_penggunaan'],
            'nazir_id' => $pakaiAkun ? (int) $v['nazir_id'] : null,
            'nama_nazir' => $pakaiAkun ? null : ($v['nama_nazir'] ?: null),
            'wakif_nama' => $v['wakif_nama'],
            'wakif_nik' => $v['wakif_nik'] ?: null,
            'desa_id' => (int) $v['desa_id'],
            'alamat' => $v['alamat'] ?: null,
            'luas_m2' => $v['luas_m2'],
            'status_sertifikat' => $v['status_sertifikat'],
            'nomor_aiw' => $v['nomor_aiw'] ?: null,
            'tanggal_aiw' => $v['tanggal_aiw'] ?: null,
            'nomor_sertifikat' => $v['nomor_sertifikat'] ?: null,
            'tanggal_sertifikat' => $v['tanggal_sertifikat'] ?: null,
            'is_published' => (bool) $v['is_published'],
            'latitude' => $v['latitude'],
            'longitude' => $v['longitude'],
            'batas_geojson' => $v['batas_geojson'] ?: null,
        ];

        $fotoLama = $this->tanah?->foto;
        if ($this->foto) {
            $data['foto'] = $this->foto->store('tanah', 'public');
        } elseif ($this->hapus_foto) {
            $data['foto'] = null;
        }

        if ($this->tanah) {
            $this->tanah->update($data);
            $tanah = $this->tanah;
        } else {
            $tanah = TanahWakaf::create([...$data, 'sumber' => 'manual']);
        }

        if ($fotoLama && array_key_exists('foto', $data) && $data['foto'] !== $fotoLama) {
            Storage::disk('public')->delete($fotoLama);
        }

        Flux::toast(variant: 'success', text: $this->tanah ? 'Data tanah wakaf berhasil diperbarui.' : "Data tanah wakaf {$tanah->kode} berhasil ditambahkan.");

        $this->redirectRoute('admin.tanah.index', navigate: true);
    }
}; ?>

<div class="space-y-6">
    <x-admin.judul-halaman :judul="($tanah ? 'Ubah' : 'Tambah').' Data Wakaf'" :deskripsi="$tanah ? $tanah->kode.' · '.$tanah->nama : null"
        :kembali="route('admin.tanah.index')" label-kembali="Kembali ke data wakaf" />

    <form wire:submit="simpan" class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-5">
            <div class="space-y-6 xl:col-span-3">
                <section class="kartu space-y-4 p-5">
                    <h2 class="font-semibold text-primary-900">Data Tanah</h2>
                    <flux:input wire:model="nama" label="Nama tanah wakaf / peruntukan" placeholder="Mis. Masjid Al-Ikhlas" required />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:select wire:model="jenis_penggunaan" label="Jenis penggunaan" required>
                            <flux:select.option value="">Pilih jenis</flux:select.option>
                            @foreach (JenisPenggunaan::opsi() as $nilai => $label)
                                <flux:select.option value="{{ $nilai }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="luas_m2" label="Luas (m²)" type="number" step="0.01" min="1" required />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:select wire:model.live="kecamatan_id" label="Kecamatan" required>
                            <flux:select.option value="">Pilih kecamatan</flux:select.option>
                            @foreach ($this->daftarKecamatan as $id => $nm)
                                <flux:select.option value="{{ $id }}">{{ $nm }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="desa_id" label="Desa/Kelurahan" required :disabled="! $this->daftarDesa">
                            <flux:select.option value="">Pilih desa/kelurahan</flux:select.option>
                            @foreach ($this->daftarDesa as $id => $nm)
                                <flux:select.option value="{{ $id }}">{{ $nm }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:textarea wire:model="alamat" label="Alamat" rows="2" />
                </section>

                <section class="kartu space-y-4 p-5">
                    <h2 class="font-semibold text-primary-900">Wakif &amp; Nazir</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="wakif_nama" label="Nama wakif" required />
                        <flux:input wire:model="wakif_nik" label="NIK wakif" inputmode="numeric" maxlength="16" placeholder="16 digit (opsional)" />
                    </div>
                    <flux:select wire:model.live="sumber_nazir" label="Data Nazir">
                        <flux:select.option value="akun">Pilih akun Nazir terdaftar</flux:select.option>
                        <flux:select.option value="teks">Tulis nama Nazir (belum punya akun)</flux:select.option>
                    </flux:select>
                    @if ($sumber_nazir === 'akun')
                        <flux:select wire:model="nazir_id" label="Akun Nazir">
                            <flux:select.option value="">Pilih Nazir</flux:select.option>
                            @foreach ($this->daftarNazir as $id => $nm)
                                <flux:select.option value="{{ $id }}">{{ $nm }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:input wire:model="nama_nazir" label="Nama Nazir" placeholder="Opsional" />
                    @endif
                </section>

                <section class="kartu space-y-4 p-5">
                    <h2 class="font-semibold text-primary-900">Legalitas</h2>
                    <flux:select wire:model.live="status_sertifikat" label="Status sertifikat" required>
                        @foreach (StatusSertifikat::cases() as $s)
                            <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="nomor_aiw" label="Nomor AIW" />
                        <flux:input wire:model="tanggal_aiw" label="Tanggal AIW" type="date" />
                        <flux:input wire:model="nomor_sertifikat" label="Nomor sertifikat" />
                        <flux:input wire:model="tanggal_sertifikat" label="Tanggal sertifikat" type="date" />
                    </div>
                    <flux:switch wire:model="is_published" label="Tampilkan di peta & halaman publik" />
                </section>
            </div>

            <div class="space-y-6 xl:col-span-2">
                <section class="kartu space-y-4 p-5">
                    <h2 class="font-semibold text-primary-900">Lokasi</h2>
                    <flux:field>
                        <flux:label>Titik lokasi &amp; batas bidang</flux:label>
                        <x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" />
                        <flux:error name="latitude" />
                        <flux:error name="batas_geojson" />
                    </flux:field>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input wire:model="latitude" label="Latitude" type="number" step="any" />
                        <flux:input wire:model="longitude" label="Longitude" type="number" step="any" />
                    </div>
                </section>

                <section class="kartu space-y-4 p-5">
                    <h2 class="font-semibold text-primary-900">Foto</h2>
                    @if ($foto && ! $errors->has('foto'))
                        <img src="{{ $foto->temporaryUrl() }}" alt="Pratinjau foto" class="h-40 w-full rounded-xl object-cover">
                    @elseif ($tanah?->foto && ! $hapus_foto)
                        <img src="{{ $tanah->fotoUrl() }}" alt="Foto {{ $tanah->nama }}" class="h-40 w-full rounded-xl object-cover">
                    @endif
                    <x-admin.unggah-berkas wire:model="foto" accept=".jpg,.jpeg,.png,.webp" label="Unggah foto" keterangan="JPG, PNG, atau WebP, maksimal 2 MB." />
                    @if ($tanah?->foto)
                        <flux:checkbox wire:model.live="hapus_foto" label="Hapus foto saat ini" />
                    @endif
                </section>
            </div>
        </div>

        <x-admin.bilah-simpan>
            <flux:button variant="ghost" :href="route('admin.tanah.index')" wire:navigate>Batal</flux:button>
            <flux:button type="submit" variant="primary" icon="check">Simpan</flux:button>
        </x-admin.bilah-simpan>
    </form>
</div>
