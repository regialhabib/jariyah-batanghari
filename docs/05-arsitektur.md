# 05 · Arsitektur Aplikasi

## Teknologi

| Lapisan | Pilihan |
|---|---|
| Backend | Laravel (versi stabil terbaru), PHP 8.4 |
| UI | Blade + Livewire (starter kit Livewire resmi) + Alpine.js (bawaan Livewire) |
| CSS | Tailwind CSS v4 (Vite) |
| Database | MySQL (Laragon) |
| Test | Pest |

## Paket

| Kebutuhan | Paket |
|---|---|
| Role/permission | `spatie/laravel-permission` |
| Audit log | `spatie/laravel-activitylog` |
| Ekspor/impor Excel | `maatwebsite/excel` |
| PDF laporan | `barryvdh/laravel-dompdf` |
| Peta | `leaflet`, `@geoman-io/leaflet-geoman-free` (npm) |
| Grafik | `chart.js` (npm) |
| Editor konten | `trix` (npm) |

**Tile peta** (gratis, tanpa API key):
- Mode "Peta": OpenStreetMap
- Mode "Satelit": Esri World Imagery

Atribusi wajib ditampilkan.

## Struktur folder (bagian penting)
```
app/
  Enums/                 Role, JenisPengajuan, StatusPengajuan, StatusSertifikat, JenisPenggunaan, ...
  Models/                User, Kecamatan, Desa, TanahWakaf, Pengajuan, PengajuanDokumen,
                         RiwayatPengajuan, Persyaratan, Artikel, PanduanLangkah, Faq, Unduhan,
                         Pengaturan, Pesan
  Policies/              TanahWakafPolicy, PengajuanPolicy, PengajuanDokumenPolicy, UserPolicy
  Services/              PengajuanWorkflow, StatistikWakaf
  Notifications/         StatusPengajuanBerubah
  Exports/ Imports/      TanahWakafExport, PengajuanExport, TanahWakafImport
  Http/Controllers/      DokumenController (unduh privat), PetaDataController (GeoJSON), LaporanPdfController
  Livewire/
    Publik/              Beranda, Tentang, InformasiWakaf, ArtikelDetail, Persyaratan, PetaWakaf,
                         TanahDetail, Panduan, Faq, Kontak, DaftarNazir
    Nazir/               Dashboard, TanahSaya, PengajuanIndex, PengajuanForm, PengajuanShow, Dokumen, Peta
    Admin/               Dashboard, TanahIndex, TanahForm, TanahImport, PengajuanIndex, PengajuanShow,
                         Sertifikat, Peta, Laporan, PenggunaIndex, PenggunaForm, PesanIndex,
                         Konten/{ArtikelIndex, PersyaratanIndex, PanduanIndex, FaqIndex,
                                 UnduhanIndex, Pengaturan}
    Shared/              PetaWakaf (komponen peta reusable), Profil
  helpers.php            setting()
resources/
  css/app.css            Token tema (@theme)
  js/app.js              Inisialisasi Leaflet, Chart.js, Trix (Alpine components)
  js/peta.js             Alpine component `petaWakaf` (tile switch, marker, polygon, geoman)
  views/
    components/layouts/  publik.blade.php, dashboard.blade.php, auth.blade.php
    components/          stat-card, badge-status, page-header, empty-state, ikon, ...
    livewire/            Mengikuti namespace komponen
    pdf/                 Template laporan PDF
routes/
  web.php                Memuat publik.php, nazir.php, admin.php
  publik.php  nazir.php  admin.php  auth.php
database/
  migrations/  factories/  seeders/ (WilayahSeeder, RoleSeeder, UserDemoSeeder, PersyaratanSeeder,
                                     KontenSeeder, TanahWakafDemoSeeder)
```

**Catatan:** rute dipecah per area (`publik.php`, `nazir.php`, `admin.php`) supaya pekerjaan paralel tidak bentrok di satu file. `web.php` hanya berisi `require`.

## Rute

### Publik
| Method | URI | Komponen | Nama rute |
|---|---|---|---|
| GET | `/` | Publik\Beranda | `beranda` |
| GET | `/tentang` | Publik\Tentang | `tentang` |
| GET | `/informasi-wakaf` | Publik\InformasiWakaf | `informasi.index` |
| GET | `/informasi-wakaf/{artikel:slug}` | Publik\ArtikelDetail | `informasi.show` |
| GET | `/persyaratan` | Publik\Persyaratan | `persyaratan` |
| GET | `/peta-wakaf` | Publik\PetaWakaf | `peta` |
| GET | `/peta-wakaf/{tanah:kode}` | Publik\TanahDetail | `peta.show` |
| GET | `/panduan` | Publik\Panduan | `panduan` |
| GET | `/faq` | Publik\Faq | `faq` |
| GET | `/kontak` | Publik\Kontak | `kontak` |
| GET | `/nazir` | Publik\DaftarNazir | `nazir.publik` |
| GET | `/unduh/{unduhan}` | Controller | `unduhan.download` |
| GET | `/api/peta` | PetaDataController | `peta.data` (GeoJSON, mendukung filter) |

### Nazir (prefix `/nazir`, nama `nazir.*`)
`dashboard`, `tanah` (index), `pengajuan` (index), `pengajuan/buat`, `pengajuan/{pengajuan}` (show + perbaikan), `dokumen`, `peta`, `profil`

### Admin (prefix `/admin`, nama `admin.*`)
- `dashboard`
- `tanah`, `tanah/buat`, `tanah/{tanah}/ubah`, `tanah/impor`
- `pengajuan` (filter jenis/status), `pengajuan/{pengajuan}` (detail + aksi pemeriksaan)
- `sertifikat`
- `peta`
- `laporan`, `laporan/excel`, `laporan/pdf`
- `pengguna`, `pengguna/buat`, `pengguna/{user}/ubah`
- `konten/artikel|persyaratan|panduan|faq|unduhan|pengaturan`
- `pesan`
- `profil`

### Dokumen privat
`GET /dokumen/{dokumen}` diarahkan ke `DokumenController@show` dengan pengecekan Policy `view`.

## Komponen bersama
- `<x-layouts.publik>`: header + nav + footer. Prop: `title`, `breadcrumb`.
- `<x-layouts.dashboard>`: sidebar per role + topbar (notifikasi, akun). Dipakai Nazir dan Admin.
- `<x-layouts.auth>`: dua kolom (panel hijau + form) untuk Login, Registrasi, dan Lupa Password.
- `<x-page-header title breadcrumb>`, `<x-stat-card icon label value satuan>`, `<x-badge-status :status>`
- `<livewire:shared.peta-wakaf mode="publik|nazir|admin" :mini="bool">`: peta reusable. Datanya diambil dari `/api/peta` (publik) atau properti komponen (dashboard).
- `App\Services\StatistikWakaf`: hitungan kartu statistik dan sebaran per kecamatan. Dipakai bersama oleh Beranda, dashboard, dan Laporan, dan menerima scope user.

## Tema Tailwind (`resources/css/app.css`)
```css
@theme {
  --color-primary-50:  #EEF7F0;
  --color-primary-100: #D6ECDB;
  --color-primary-500: #2E8B4E;
  --color-primary-700: #1F6B3A;  /* warna utama tombol & header */
  --color-primary-900: #123F22;
  --color-accent:      #8BC34A;
  --color-cream:       #F7F8F3;
  --font-sans: "Poppins", ui-sans-serif, system-ui, sans-serif;
}
```

**Status peta:**
- sudah = `#2E8B4E`
- belum = `#F59E0B`
- proses = `#3B82F6`

## Konvensi kode
- Bahasa UI: Indonesia. Pesan validasi Indonesia (`lang/id`), `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`.
- Nama model/kolom domain memakai istilah Indonesia. Kode infrastruktur memakai nama Laravel standar.
- Logika bisnis ada di Service dan Policy, bukan di komponen Livewire.
- Setiap komponen Livewire full-page menetapkan `#[Layout]` dan `#[Title]`.
