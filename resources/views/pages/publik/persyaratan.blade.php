<?php

use App\Enums\JenisPengajuan;
use App\Models\Persyaratan;
use App\Models\Unduhan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Persyaratan')] class extends Component {
    /**
     * Persyaratan per jenis pengajuan, urut sesuai enum.
     *
     * @return list<array{jenis: JenisPengajuan, items: \Illuminate\Support\Collection<int, Persyaratan>}>
     */
    #[Computed]
    public function kelompok(): array
    {
        $semua = Persyaratan::query()->orderBy('urutan')->orderBy('id')->get()
            ->groupBy(fn (Persyaratan $p) => $p->jenis_pengajuan->value);

        return array_map(fn (JenisPengajuan $jenis) => [
            'jenis' => $jenis,
            'items' => $semua->get($jenis->value, collect()),
        ], JenisPengajuan::cases());
    }

    /**
     * Tujuan tombol "Unduh Formulir": formulir pertama yang sudah tersedia, atau halaman Panduan.
     */
    #[Computed]
    public function urlFormulir(): string
    {
        $formulir = Unduhan::query()
            ->whereNotNull('file')->where('file', '!=', '')
            ->orderBy('urutan')->orderBy('id')
            ->first();

        return $formulir?->url() ?? route('panduan').'#dokumen-penting';
    }
}; ?>

<div>
    <x-page-header title="Persyaratan" icon="clipboard-document-check" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Persyaratan']]" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-primary-900">Daftar Persyaratan Pengajuan Tanah Wakaf</h2>
        <p class="mt-1 text-zinc-600">Berikut adalah dokumen yang perlu disiapkan sesuai dengan jenis pengajuan:</p>

        <div class="mt-6 grid gap-5 lg:grid-cols-3">
            @foreach ($this->kelompok as $kelompok)
                <section wire:key="jenis-{{ $kelompok['jenis']->value }}" class="kartu p-5 sm:p-6" aria-labelledby="judul-{{ $kelompok['jenis']->value }}">
                    <div class="flex items-center gap-3 border-b border-primary-100 pb-4">
                        <x-ikon-kotak icon="document-text" />
                        <h3 id="judul-{{ $kelompok['jenis']->value }}" class="font-semibold leading-snug text-primary-900">
                            {{ $loop->iteration }}. Syarat {{ $kelompok['jenis']->label() }}
                        </h3>
                    </div>

                    @if ($kelompok['items']->isEmpty())
                        <p class="mt-4 text-sm text-zinc-500">Persyaratan belum ditambahkan.</p>
                    @else
                        <ul class="mt-4 space-y-3">
                            @foreach ($kelompok['items'] as $syarat)
                                <li wire:key="syarat-{{ $syarat->id }}" class="flex gap-2.5 text-sm text-zinc-700">
                                    <flux:icon.check-circle variant="solid" class="size-5 shrink-0 text-primary-600" />
                                    <span>
                                        {{ $syarat->nama }}
                                        @unless ($syarat->wajib)
                                            <span class="text-zinc-500">(jika ada)</span>
                                        @endunless
                                        @if (filled($syarat->keterangan))
                                            <span class="mt-0.5 block text-xs text-zinc-500">{{ $syarat->keterangan }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="mt-8 grid gap-3 sm:grid-cols-3">
            <a href="{{ route('panduan') }}" wire:navigate class="btn-primary">
                <flux:icon.document-text class="size-4" /> Lihat Persyaratan Lengkap
            </a>
            <a href="{{ $this->urlFormulir }}" class="btn-outline">
                <flux:icon.arrow-down-tray class="size-4" /> Unduh Formulir
            </a>
            <a href="{{ route('register') }}" class="btn-outline">
                <flux:icon.user class="size-4" /> Daftar Akun
            </a>
        </div>
    </div>
</div>
