<?php

use App\Models\Kecamatan;
use App\Models\TanahWakaf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * Daftar Nazir publik. Privasi (docs/06-keamanan.md): hanya nama, kecamatan, dan jumlah tanah
 * yang dikelola. NIK, No. HP, dan email TIDAK PERNAH dipilih dari database di halaman ini.
 *
 * Sumber data: tanah wakaf yang dipublikasikan, baik yang terhubung ke akun Nazir (aktif)
 * maupun yang hanya mencatat nama Nazir (data impor/manual).
 */
new #[Layout('layouts::publik'), Title('Daftar Nazir')] class extends Component {
    use WithPagination;

    public const PER_HALAMAN = 12;

    #[Url(as: 'kecamatan', except: '')]
    public string $kecamatan = '';

    #[Url(as: 'cari', except: '')]
    public string $cari = '';

    public function updatedKecamatan(): void
    {
        $this->resetPage();
    }

    public function updatedCari(): void
    {
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->reset('kecamatan', 'cari');
        $this->resetPage();
    }

    /**
     * @return EloquentCollection<int, Kecamatan>
     */
    #[Computed]
    public function kecamatans(): EloquentCollection
    {
        return Kecamatan::query()->orderBy('nama')->get(['id', 'nama']);
    }

    /**
     * Semua Nazir beserta kecamatan dan jumlah tanah publik yang dikelola.
     *
     * @return Collection<int, array{nama: string, kecamatan: array<int, string>, jumlah: int}>
     */
    #[Computed]
    public function semuaNazir(): Collection
    {
        $baris = TanahWakaf::query()
            ->where('tanah_wakafs.is_published', true)
            ->join('desas', 'desas.id', '=', 'tanah_wakafs.desa_id')
            ->join('kecamatans', 'kecamatans.id', '=', 'desas.kecamatan_id')
            ->leftJoin('users', 'users.id', '=', 'tanah_wakafs.nazir_id')
            ->where(function (Builder $q) {
                $q->where(fn (Builder $akun) => $akun->whereNotNull('tanah_wakafs.nazir_id')->where('users.is_active', true))
                    ->orWhere(fn (Builder $nama) => $nama->whereNull('tanah_wakafs.nazir_id')
                        ->whereNotNull('tanah_wakafs.nama_nazir')->where('tanah_wakafs.nama_nazir', '!=', ''));
            })
            ->groupBy('tanah_wakafs.nazir_id', 'users.name', 'tanah_wakafs.nama_nazir', 'kecamatans.id', 'kecamatans.nama')
            ->selectRaw('tanah_wakafs.nazir_id, users.name as nama_akun, tanah_wakafs.nama_nazir, kecamatans.id as kecamatan_id, kecamatans.nama as kecamatan_nama, count(*) as jumlah')
            ->toBase()
            ->get();

        return $baris
            ->groupBy(fn (object $b) => $b->nazir_id
                ? 'akun-'.$b->nazir_id
                : 'nama-'.Str::lower(Str::squish($b->nama_nazir)))
            ->map(fn (Collection $grup) => [
                'nama' => Str::squish($grup->first()->nama_akun ?? $grup->first()->nama_nazir),
                'kecamatan' => $grup->pluck('kecamatan_nama', 'kecamatan_id')->sort()->all(),
                'jumlah' => (int) $grup->sum('jumlah'),
            ])
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    #[Computed]
    public function nazirs(): LengthAwarePaginator
    {
        $kecamatanId = (int) $this->kecamatan;
        $cari = Str::lower(trim($this->cari));

        $hasil = $this->semuaNazir
            ->when($kecamatanId > 0, fn (Collection $c) => $c->filter(fn (array $n) => array_key_exists($kecamatanId, $n['kecamatan'])))
            ->when($cari !== '', fn (Collection $c) => $c->filter(fn (array $n) => Str::contains(Str::lower($n['nama']), $cari)))
            ->values();

        $halaman = max(1, min($this->getPage(), (int) ceil(max(1, $hasil->count()) / self::PER_HALAMAN)));

        return new LengthAwarePaginator(
            $hasil->forPage($halaman, self::PER_HALAMAN)->values(),
            $hasil->count(),
            self::PER_HALAMAN,
            $halaman,
            ['pageName' => 'page'],
        );
    }

    public static function inisial(string $nama): string
    {
        return collect(preg_split('/\s+/', trim($nama)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $kata) => Str::upper(Str::substr($kata, 0, 1)))
            ->join('');
    }
}; ?>

<div>
    <x-page-header title="Daftar Nazir" icon="user-group" subtitle="Nazir yang mengelola tanah wakaf terdaftar di Kabupaten Batang Hari."
        :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Daftar Nazir']]" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Filter --}}
        <div class="kartu p-4 sm:p-5">
            <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <flux:input wire:model.live.debounce.400ms="cari" label="Cari Nazir" placeholder="Ketik nama Nazir..." icon="magnifying-glass" clearable />
                <flux:select wire:model.live="kecamatan" label="Kecamatan">
                    <flux:select.option value="">Semua Kecamatan</flux:select.option>
                    @foreach ($this->kecamatans as $kec)
                        <flux:select.option value="{{ $kec->id }}" wire:key="kec-{{ $kec->id }}">{{ $kec->nama }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if ($kecamatan !== '' || $cari !== '')
                    <flux:button wire:click="resetFilter" variant="ghost" icon="x-mark">Reset</flux:button>
                @endif
            </div>
        </div>

        <p class="mt-6 text-sm text-zinc-600" aria-live="polite">
            Menampilkan <span class="font-semibold text-primary-900">{{ number_format($this->nazirs->total(), 0, ',', '.') }}</span> Nazir
            @if ($kecamatan !== '' && $this->kecamatans->firstWhere('id', (int) $kecamatan))
                di {{ $this->kecamatans->firstWhere('id', (int) $kecamatan)->nama }}
            @endif
        </p>

        @if ($this->nazirs->isEmpty())
            <div class="kartu mt-4">
                <x-empty-state icon="user-group" title="Nazir tidak ditemukan" description="Belum ada Nazir yang cocok dengan filter yang dipilih.">
                    @if ($kecamatan !== '' || $cari !== '')
                        <flux:button wire:click="resetFilter" size="sm" class="mt-2">Tampilkan semua</flux:button>
                    @endif
                </x-empty-state>
            </div>
        @else
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-60">
                @foreach ($this->nazirs as $nazir)
                    <li wire:key="nazir-{{ md5($nazir['nama'].implode(',', array_keys($nazir['kecamatan']))) }}" class="kartu flex items-start gap-4 p-5">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-800" aria-hidden="true">
                            {{ $this->inisial($nazir['nama']) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-primary-900">{{ $nazir['nama'] }}</p>
                            <p class="mt-1 flex items-start gap-1.5 text-sm text-zinc-600">
                                <flux:icon.map-pin variant="micro" class="mt-0.5 shrink-0 text-primary-600" />
                                <span><span class="sr-only">Kecamatan: </span>{{ implode(', ', $nazir['kecamatan']) }}</span>
                            </p>
                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">
                                <flux:icon.home-modern variant="micro" />
                                {{ $nazir['jumlah'] }} bidang tanah wakaf
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>

            <flux:pagination :paginator="$this->nazirs" class="mt-6" />
        @endif

        <p class="mt-8 flex items-start gap-2 text-xs text-zinc-500">
            <flux:icon.shield-check variant="micro" class="mt-0.5 shrink-0" />
            Untuk melindungi privasi, data pribadi Nazir (NIK, nomor HP, dan email) tidak ditampilkan. Untuk keperluan lebih lanjut, silakan
            <a href="{{ route('kontak') }}" wire:navigate class="text-primary-700 underline">hubungi kami</a>.
        </p>
    </div>
</div>
