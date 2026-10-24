# Komponen Peta (Fase 4)

Peta memakai Leaflet + Alpine (`resources/js/peta.js`, komponen `petaWakaf`). Untuk halaman,
**pakai komponen Blade** `<x-peta>` dan `<x-peta-pilih>`; `x-data="petaWakaf(...)"` langsung hanya
untuk kasus khusus.

| Bagian | Berkas |
|---|---|
| Alpine `petaWakaf` | `resources/js/peta.js` (sudah di-import `app.js`) |
| Peta tampilan | `resources/views/components/peta.blade.php` |
| Pemilih lokasi form | `resources/views/components/peta-pilih.blade.php` |
| API GeoJSON publik | `GET /api/peta` (`route('peta.data')`), `App\Http\Controllers\PetaDataController` |
| Halaman | `pages::publik.peta-wakaf` (H5), `pages::publik.tanah-detail` |

Fitur bawaan: toggle **Peta** (OpenStreetMap) / **Satelit** (Esri World Imagery) beserta atribusinya,
tombol zoom, marker lingkaran berwarna sesuai status (sudah `#2E8B4E`, belum `#F59E0B`, proses `#3B82F6`),
polygon `batas` jika ada, popup (foto, nama, Nazir, Desa, Kecamatan, Luas, Status, tombol "Lihat Detail"),
legenda, *fit bounds* ke data, keadaan kosong/memuat/galat, dan pembersihan otomatis (`destroy()`) saat
pindah halaman dengan `wire:navigate`. Semua teks di popup di-*escape*, dan URL yang bukan http(s)/path
relatif dibuang.

---

## 1. `<x-peta>` — menampilkan peta

```blade
<x-peta mode="publik|data" :data="$array" url="" tinggi="h-96" />
```

| Prop | Default | Keterangan |
|---|---|---|
| `mode` | `publik` | `publik` = ambil GeoJSON dari `url`; `data` = tampilkan array `data` |
| `url` | `route('peta.data')` | Hanya mode publik |
| `filter` | `[]` | Filter awal mode publik: `['q'=>'', 'kecamatan'=>'', 'jenis'=>'', 'status'=>'']` |
| `event` | `peta-filter` | Event window untuk memuat ulang dengan filter baru |
| `data` | `[]` | Mode data: array objek `TanahWakaf::dataPublik()` (Feature GeoJSON juga diterima) |
| `tinggi` | `h-96` | Kelas tinggi Tailwind (boleh responsif, mis. `h-72 lg:h-96`) |
| `legenda` | `true` | Tampilkan legenda status |
| `scroll-zoom` | `true` | Zoom dengan roda mouse (matikan untuk peta kecil di halaman panjang) |
| `tombol-detail` | `true` | Tombol "Lihat Detail" di popup |
| `pusat`, `zoom` | `[-1.70, 103.15]`, `10` | Tampilan awal jika data kosong |
| `label` | `Peta sebaran tanah wakaf` | `aria-label` area peta |

Atribut lain (`class`, `wire:key`, …) diteruskan ke elemen pembungkus. Pembungkus memakai `wire:ignore`
dan `isolate` (agar panel Leaflet tidak menimpa header yang *sticky*).

### Peta publik dengan filter dari Livewire

```blade
<x-peta mode="publik" :filter="['status' => 'sudah']" tinggi="h-[520px]" />
```

```php
// Di komponen Livewire: kirim filter baru, peta memuat ulang /api/peta?…
$this->dispatch('peta-filter', filter: ['q' => $this->q, 'kecamatan' => $this->kecamatan, 'jenis' => '', 'status' => '']);
```

Nilai kosong (`''`/`null`) diabaikan. Lihat `pages/publik/peta-wakaf.blade.php` untuk contoh lengkap
(properti `#[Url]`, tombol Terapkan, ringkasan).

### Peta mini di dashboard (mode data)

Data dikirim langsung, tanpa request tambahan. **Selalu lewat `dataPublik()`**: jangan kirim model
atau array mentah (berisi NIK).

```blade
{{-- Dashboard Nazir: tanah miliknya --}}
<x-peta mode="data" tinggi="h-72" :scroll-zoom="false"
    :data="$this->tanahs->map->dataPublik()->all()" />
```

```php
#[Computed]
public function tanahs()
{
    return TanahWakaf::query()->forUser(auth()->user())->with(['desa.kecamatan', 'nazir'])->get();
}
```

- `dataPublik()` membuat `url` ke halaman publik `peta.show`. Halaman itu 404 untuk tanah yang belum
  dipublikasikan, jadi untuk dashboard petugas/nazir yang memuat tanah belum terbit, timpa `url`:
  `->map(fn ($t) => [...$t->dataPublik(), 'url' => route('admin.tanah.edit', $t)])`, atau pakai
  `:tombol-detail="false"`.
- Data tidak diperbarui otomatis saat Livewire me-*render* ulang (pembungkus `wire:ignore`). Jika data
  bisa berubah (mis. filter di dashboard), beri kunci agar peta dibuat ulang:
  `wire:key="peta-{{ md5(json_encode($ids)) }}"`. Alternatif: mode publik + event `peta-filter`.
- Untuk ratusan bidang di dashboard admin, lebih hemat pakai mode publik (GeoJSON di-*fetch*) daripada
  menanam semua data di HTML.

### Satu titik (halaman detail)

```blade
<x-peta mode="data" :data="[$tanah->dataPublik()]" :tombol-detail="false" :scroll-zoom="false" tinggi="h-80" />
```

Satu titik tanpa polygon di-zoom ke level 16; jika ada polygon, peta menyesuaikan ke batasnya.

---

## 2. `<x-peta-pilih>` — pemilih lokasi di form Livewire

```blade
<x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" tinggi="h-80" />
```

| Prop | Default | Keterangan |
|---|---|---|
| `lat`, `lng` | `latitude`, `longitude` | **Nama properti** Livewire (boleh bertitik: `form.latitude`) |
| `batas` | `null` | Nama properti polygon GeoJSON. `null` = tanpa alat gambar polygon (Geoman tidak dimuat) |
| `live` | `false` | `true` = setiap perubahan langsung request ke server (`entangle(..., true)`) |
| `tinggi` | `h-80` | Kelas tinggi |
| `pusat`, `zoom` | Batang Hari, 10 | Tampilan awal jika belum ada titik |
| `petunjuk` | `true` | Teks petunjuk + koordinat + tombol "Hapus titik" di bawah peta |

Harus berada **di dalam komponen Livewire** (memakai `$wire.entangle`). Komponen ini setara dengan:

```blade
<div wire:ignore x-data="petaWakaf({ mode: 'pilih', lat: $wire.entangle('latitude'), lng: $wire.entangle('longitude'), batas: $wire.entangle('batas_geojson') })">
    <div x-ref="peta" class="h-80"></div>
</div>
```

Perilaku:
- Klik peta → penanda dipasang, `latitude`/`longitude` diisi (angka, dibulatkan 7 desimal). Penanda
  bisa digeser. Tombol **Lokasi Saya** memakai GPS perangkat.
- Jika properti diubah dari server atau input lain (`wire:model="latitude"`), penanda ikut pindah;
  `null` menghapus penanda.
- Dengan `batas`: toolbar Geoman (bahasa Indonesia) untuk menggambar polygon/persegi, mengedit titik
  sudut, menggeser, dan menghapus. Hanya **satu** polygon disimpan (gambar baru menggantikan yang lama).
  Nilai yang disimpan berupa **geometri GeoJSON** `{"type":"Polygon","coordinates":[[[lng,lat],…]]}`,
  atau `null` bila dihapus. Nilai lama berbentuk Feature/FeatureCollection juga bisa dibaca.
- Nilai disinkronkan ke Livewire secara *deferred*: terkirim bersama aksi berikutnya (mis. `wire:submit`).
- CSS Geoman dimuat lewat `@push('head')` dan, sebagai cadangan, disuntikkan JS jika komponen muncul
  setelah request Livewire (mis. langkah wizard). JS Geoman di-*import* dinamis (chunk terpisah).

Contoh komponen form:

```php
public ?float $latitude = null;
public ?float $longitude = null;
public ?array $batas_geojson = null;

protected function rules(): array
{
    return [
        'latitude' => ['required', 'numeric', 'between:-90,90'],
        'longitude' => ['required', 'numeric', 'between:-180,180'],
        'batas_geojson' => ['nullable', 'array'],
        'batas_geojson.type' => ['required_with:batas_geojson', 'in:Polygon,MultiPolygon'],
        'batas_geojson.coordinates' => ['required_with:batas_geojson', 'array'],
    ];
}
```

```blade
<flux:field>
    <flux:label>Lokasi tanah wakaf</flux:label>
    <x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" />
    <flux:error name="latitude" />
</flux:field>

{{-- Opsional: input manual yang tetap sinkron dengan peta --}}
<div class="grid grid-cols-2 gap-3">
    <flux:input wire:model="latitude" label="Latitude" type="number" step="any" />
    <flux:input wire:model="longitude" label="Longitude" type="number" step="any" />
</div>
```

Model `TanahWakaf` meng-*cast* `batas_geojson` sebagai `array`, jadi nilainya bisa langsung disimpan.

---

## 3. Kontrak JS `petaWakaf(opsi)`

Elemen root `x-data` sebaiknya memakai `wire:ignore` + `relative isolate`, dan berisi `<div x-ref="peta">`
(tanpa `x-ref`, elemen root dipakai sebagai wadah peta).

| Opsi | Mode | Keterangan |
|---|---|---|
| `mode` | semua | `publik` \| `data` \| `pilih` |
| `url`, `filter`, `event` | publik | Endpoint GeoJSON, filter awal, nama event filter (default `peta-filter`) |
| `data` | data | Array `dataPublik()` / Feature |
| `lat`, `lng`, `batas` | pilih | Nilai atau `$wire.entangle(...)`. Jika **kunci** `batas` ada, alat polygon aktif |
| `cssGeoman` | pilih | URL CSS Geoman (dimuat jika belum ada) |
| `pusat`, `zoom`, `legenda`, `scrollZoom`, `tombolDetail` | semua | Lihat tabel `<x-peta>` |
| `batasWilayah` | semua | URL GeoJSON garis batas kabupaten (garis putus-putus hijau) |

State reaktif yang bisa dipakai di markup dalam root: `memuat`, `galat`, `jumlah` (null sebelum dimuat),
`ringkasan` (`{total, kecamatan, desa}` dari API), `basemap` (`peta`/`satelit`), `filter`, `lat`, `lng`,
`batas`. Method: `muat(filter?)`, `gantiBasemap('satelit')`, `pindah({lat, lng, zoom})`, `hapusTitik()`.

Event:
- Didengar (window): `peta-filter` → `{ filter: {...} }`; `peta-pindah` → `{ lat, lng, zoom? }`
  (mis. `$this->dispatch('peta-pindah', lat: -1.70, lng: 103.27, zoom: 14)` saat kecamatan di form dipilih).
- Dikirim (bubbling dari root): `peta-dimuat` → `{ jumlah, ringkasan }`
  (`x-on:peta-dimuat="total = $event.detail.jumlah"`).

---

## 4. API `GET /api/peta`

Query: `q` (nama/kode/nama desa), `kecamatan` (id), `jenis` (`JenisPenggunaan`), `status`
(`StatusSertifikat`). Nilai yang tidak valid diabaikan. Dibatasi 60 request/menit.

```json
{
  "type": "FeatureCollection",
  "features": [
    { "type": "Feature",
      "geometry": { "type": "Point", "coordinates": [103.27, -1.70] },
      "properties": { "kode": "TW-06-0001", "nama": "…", "nazir": "…", "desa": "…", "kecamatan": "…",
                      "luas_m2": 1200, "jenis_penggunaan": "masjid", "jenis_penggunaan_label": "Masjid",
                      "status": "sudah", "status_label": "Sudah Bersertifikat", "warna": "#2E8B4E",
                      "foto": null, "lat": -1.70, "lng": 103.27, "batas": null, "url": "…/peta-wakaf/TW-06-0001" } }
  ],
  "ringkasan": { "total": 1, "kecamatan": 1, "desa": 1 }
}
```

Di PHP, query dan ringkasan yang sama tersedia:
`PetaDataController::query($filter)` (Builder tanah terpublikasi + filter) dan
`PetaDataController::ringkasan($filter)`.

---

## 5. Garis batas Kabupaten Batang Hari

Mockup meminta garis batas kabupaten. Belum ada data resminya. Jika file GeoJSON ditaruh di
`public/geo/batang-hari.geojson`, `<x-peta>` otomatis menggambarnya (lihat docs/08-kebutuhan-data.md).
