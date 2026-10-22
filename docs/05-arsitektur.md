# 05 · Arsitektur Aplikasi

> Diperbarui setelah Fase 0–1 mengikuti struktur nyata starter kit Livewire terbaru (lihat CHANGELOG).

## Teknologi

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Autentikasi | Laravel Fortify (login, registrasi, lupa password, verifikasi email, konfirmasi password) |
| UI | Livewire 4 **single-file components** (SFC) + Blade + Alpine.js (bawaan Livewire) |
| Komponen UI | Flux UI (gratis) untuk input, tombol, dropdown, modal, toast, sidebar |
| CSS | Tailwind CSS v4 (Vite / vite-plus) |
| Database | MySQL 8 (Laragon), `jariyah_batanghari` |
| Test | PHPUnit (bawaan starter kit), database SQLite in-memory |
| Bahasa | `laravel-lang` (`lang/id`, `lang/id.json`) |

## Paket

| Kebutuhan | Paket |
|---|---|
| Role/permission | `spatie/laravel-permission` v8 |
| Audit log | `spatie/laravel-activitylog` v5 (namespace `Spatie\Activitylog\Models\Concerns\LogsActivity`, `Spatie\Activitylog\Support\LogOptions`, method `dontLogEmptyChanges()`) |
| Ekspor/impor Excel | `maatwebsite/excel` v4 |
| PDF laporan | `barryvdh/laravel-dompdf` v3 |
| Sanitasi HTML | `mews/purifier` (helper `clean()`) |
| Peta | `leaflet`, `@geoman-io/leaflet-geoman-free` (npm) |
| Grafik | `chart.js` (npm) |
| Editor konten | `trix` (npm) |

**Tile peta** (gratis, tanpa API key):
- Mode "Peta": OpenStreetMap
- Mode "Satelit": Esri World Imagery

Atribusi wajib ditampilkan.

**CSS Geoman** dimuat dari `public/vendor/leaflet-geoman/leaflet-geoman.css` lewat `@push('head')`, hanya di halaman yang menggambar polygon. File ini tidak bisa lewat Vite karena data URI SVG-nya merusak build.

## Struktur folder
```
app/
  Enums/          Role, JenisPengajuan, StatusPengajuan, StatusSertifikat, JenisPenggunaan,
                  StatusValidasiDokumen, KategoriDokumen  — semua punya label() (+ warna())
  Models/         User, Kecamatan, Desa, TanahWakaf, Pengajuan, PengajuanDokumen, RiwayatPengajuan,
                  Persyaratan, Artikel, PanduanLangkah, Faq, Unduhan, Pengaturan, Pesan
  Policies/       PengajuanPolicy (view, update, kirim, delete, periksa), PengajuanDokumenPolicy,
                  TanahWakafPolicy (view, create, update, delete, impor), UserPolicy
  Services/       PengajuanWorkflow (satu-satunya pintu perubahan status), StatistikWakaf
  Notifications/  StatusPengajuanBerubah (ke Nazir), PengajuanMasuk (ke pemeriksa)
  Http/Controllers/  DokumenController (unduh privat), PetaDataController (GeoJSON publik),
                     UnduhanController (formulir publik), LaporanController (Excel/PDF)
  Http/Middleware/   PastikanAkunAktif (mengeluarkan akun nonaktif)
  Exports/ Imports/  (dibuat di Fase 6–7)
  helpers.php     setting(), setting_list(), format_luas()
resources/
  css/app.css     Token tema (@theme) + kelas .kartu .btn-primary .btn-outline .konten-artikel
  js/app.js       Hanya import modul: peta.js, grafik.js, editor.js
  js/peta.js      Alpine component `petaWakaf` (Fase 4)
  js/grafik.js    Alpine component `grafikSebaran` (Chart.js)
  js/editor.js    Trix
  views/
    layouts/publik.blade.php      Header + nav + footer publik
    layouts/app.blade.php         Layout default Livewire = dashboard (sidebar per role)
    layouts/app/sidebar.blade.php Sidebar hijau + topbar (lonceng notifikasi, menu akun)
    layouts/auth/*.blade.php      Layout halaman auth
    components/   logo, app-logo-icon, page-header, ikon-kotak, stat-card, badge-status,
                  empty-state, ornamen-daun
    livewire/     notifikasi-lonceng (SFC)
    pages/        Halaman full-page (SFC):
      publik/     beranda, tentang, informasi-wakaf, artikel, persyaratan, peta-wakaf,
                  tanah-detail, panduan, faq, kontak, daftar-nazir
      auth/       login, register, forgot-password, reset-password, verify-email, confirm-password
      nazir/      dashboard, tanah, pengajuan/{index,buat,show}, dokumen, peta
      admin/      dashboard, tanah/{index,form,impor}, pengajuan/{index,show}, sertifikat, peta,
                  laporan, pesan, aktivitas, pengguna/{index,form}, konten/{artikel,persyaratan,
                  panduan,faq,unduhan,pengaturan}
      settings/   profile, security (dipakai sebagai menu "Profil")
routes/
  web.php         require publik.php, nazir.php, admin.php, settings.php + /dashboard + /dokumen/{id}
  publik.php  nazir.php  admin.php  settings.php
```

## Konvensi halaman (Livewire 4 SFC)
Satu file Blade berisi kelas komponen anonim dan markup-nya:
```php
<?php
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Persyaratan')] class extends Component {
    // properti, mount(), #[Computed], aksi...
}; ?>

<div> ...markup... </div>
```
- Halaman publik memakai `#[Layout('layouts::publik')]`.
- Halaman dashboard (Nazir/Admin) **tidak perlu** atribut Layout, karena defaultnya `layouts::app`.
- Rute didaftarkan dengan `Route::livewire('uri', 'pages::folder.nama')`.
- Judul dinamis: `public function render() { return $this->view()->title(...); }`.
- Logika bisnis tetap di Service dan Policy, tidak di komponen. Komponen memanggil `$this->authorize(...)`.

## Rute

### Publik (`routes/publik.php`)
| URI | Halaman | Nama |
|---|---|---|
| `/` | publik.beranda | `beranda` |
| `/tentang` | publik.tentang | `tentang` |
| `/informasi-wakaf` | publik.informasi-wakaf | `informasi.index` |
| `/informasi-wakaf/{artikel:slug}` | publik.artikel | `informasi.show` |
| `/persyaratan` | publik.persyaratan | `persyaratan` |
| `/peta-wakaf` | publik.peta-wakaf | `peta` |
| `/peta-wakaf/{tanah:kode}` | publik.tanah-detail | `peta.show` |
| `/panduan` | publik.panduan | `panduan` |
| `/faq` | publik.faq | `faq` |
| `/kontak` | publik.kontak | `kontak` |
| `/daftar-nazir` | publik.daftar-nazir | `nazir.publik` |
| `/unduh/{unduhan}`, `/unduh/semua` | UnduhanController | `unduhan.download`, `unduhan.semua` |
| `/api/peta` | PetaDataController | `peta.data` |

### Umum (login)
- `/dashboard` (`dashboard`): diteruskan ke dashboard sesuai role. Ini juga tujuan default Fortify.
- `/dokumen/{dokumen}` (`dokumen.show`): dokumen privat, dicek dengan Policy.
- `/settings/profile` (`profile.edit`) dan `/settings/security` (`security.edit`): menu Profil.

### Nazir (`/nazir/*`, nama `nazir.*`, middleware `auth, verified, role:nazir`)
`dashboard`, `tanah`, `pengajuan.index`, `pengajuan.create` (`/nazir/pengajuan/buat`), `pengajuan.show`, `dokumen`, `peta`

### Admin (`/admin/*`, nama `admin.*`, middleware `auth, verified, role:admin_bpn|bpn|kua`)
- **Semua petugas:** `dashboard`, `tanah.index`, `pengajuan.index`, `pengajuan.show`, `peta`, `laporan`
- **Permission per rute:**
  - `tanah.create` dan `tanah.edit` (`tanah.kelola`)
  - `tanah.impor` (`tanah.impor`)
  - `sertifikat` (`sertifikat.catat-terbit`)
  - `laporan.excel` dan `laporan.pdf` (`laporan.ekspor`)
  - `pesan` (`pesan.lihat`)
- **Khusus admin:**
  - `pengguna.index`, `pengguna.create`, `pengguna.edit`, `aktivitas` (`pengguna.kelola`)
  - `konten.{artikel,persyaratan,panduan,faq,unduhan,pengaturan}` (`konten.kelola`)

## Komponen bersama (siap pakai)
| Komponen | Pemakaian |
|---|---|
| `<x-page-header title="" :breadcrumb="[['label'=>'Beranda','url'=>route('beranda')],['label'=>'X']]" subtitle="" icon="" />` | Banner judul halaman publik |
| `<x-stat-card icon="map-pin" label="Total Tanah Wakaf" :value="125" satuan="Bidang" href="" />` | Kartu statistik |
| `<x-ikon-kotak icon="document-text" size="sm\|md\|lg" variant="solid\|soft" />` | Ikon heroicon dalam kotak hijau |
| `<x-badge-status :status="$enum" />` | Badge untuk enum yang punya label() + warna() |
| `<x-empty-state icon="" title="" description="">slot</x-empty-state>` | Keadaan kosong |
| `<x-logo :putih="bool" />` | Logo lengkap JARIYAH / BATANG HARI |
| `<x-ornamen-daun class="" />` | Ornamen daun dekoratif |
| Kelas CSS `.kartu`, `.btn-primary`, `.btn-outline`, `.konten-artikel` | Gaya dasar |
| `x-data="grafikSebaran(@js($data))"` + `<canvas x-ref="canvas">` | Grafik batang per kecamatan |
| `App\Services\StatistikWakaf` | `ringkasan(?User)`, `perKecamatan(?User)`, `pengajuanPerStatus(User)` |
| `TanahWakaf::dataPublik()` | Data aman untuk publik, tanpa NIK/HP |
| `<x-peta mode="publik\|data" :data="[...]" />`, `<x-peta-pilih lat="latitude" lng="longitude" batas="batas_geojson" />` | Peta Leaflet dan pemilih lokasi, lihat [komponen-peta.md](komponen-peta.md) |
| Ikon | Nama heroicons via `<flux:icon name="..." />` atau `<flux:icon.nama />` |

## Tema
Token warna di `resources/css/app.css`:
- `primary-50…950` (utama `primary-700` = #1F6B3A)
- `daun`, `krem`
- `status-sudah`, `status-belum`, `status-proses`

Aksen Flux = `primary-700`. Font Poppins (Bunny Fonts via plugin Vite). Situs hanya mode terang, karena dark mode starter kit dimatikan.

**Warna status di peta:**
- sudah = `#2E8B4E`
- belum = `#F59E0B`
- proses = `#3B82F6`

## Perintah penting
Composer global di laptop ini versi lama dan butuh admin, jadi pakai Composer Laragon:
```
php C:/laragon/bin/composer/composer.phar <perintah>
php artisan migrate:fresh --seed     # reset DB + data demo
php artisan test                     # semua test
npm run build                        # build aset (atau composer run dev untuk pengembangan)
vendor/bin/pint --dirty              # rapikan format kode
```
