<?php

use App\Models\TanahWakaf;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Peta Wakaf Saya')] class extends Component {
    /**
     * @return Collection<int, TanahWakaf>
     */
    #[Computed]
    public function tanahs(): Collection
    {
        return TanahWakaf::query()->forUser(auth()->user())
            ->with(['desa.kecamatan', 'nazir', 'pengajuans:id,tanah_wakaf_id'])
            ->get();
    }
}; ?>

<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1">Peta Wakaf Saya</flux:heading>
            <flux:text class="mt-1">Lokasi tanah wakaf yang Anda kelola. Klik titik untuk melihat ringkasan.</flux:text>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <span class="rounded-full bg-white px-3 py-1 font-medium text-primary-900 shadow-sm">{{ $this->tanahs->count() }} bidang</span>
            @foreach (\App\Enums\StatusSertifikat::cases() as $kasus)
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $kasus->warna() }}">
                    {{ $kasus->label() }}: {{ $this->tanahs->where('status_sertifikat', $kasus)->count() }}
                </span>
            @endforeach
        </div>
    </div>

    <x-nazir.peta-tanah :tanahs="$this->tanahs" tinggi="h-[calc(100vh-15rem)] min-h-[420px]" />
</div>
