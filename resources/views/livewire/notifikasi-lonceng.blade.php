<?php

use App\Models\Pengajuan;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Lonceng notifikasi di topbar dashboard. Notifikasi berasal dari database notifications
 * (mis. StatusPengajuanBerubah) dengan data berisi 'pengajuan_id', 'judul', 'pesan'.
 */
new class extends Component {
    #[Computed]
    public function belumDibaca(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function terbaru()
    {
        return auth()->user()->notifications()->latest()->limit(6)->get();
    }

    public function buka(string $id): void
    {
        /** @var DatabaseNotification|null $notifikasi */
        $notifikasi = auth()->user()->notifications()->whereKey($id)->first();
        if (! $notifikasi) {
            return;
        }

        $notifikasi->markAsRead();

        $pengajuanId = $notifikasi->data['pengajuan_id'] ?? null;
        if ($pengajuanId && $pengajuan = Pengajuan::find($pengajuanId)) {
            $route = auth()->user()->isNazir() ? 'nazir.pengajuan.show' : 'admin.pengajuan.show';
            $this->redirectRoute($route, $pengajuan, navigate: true);
        }
    }

    public function tandaiSemua(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }
}; ?>

<flux:dropdown position="bottom" align="end">
    <button type="button" class="relative rounded-full p-2 text-zinc-600 hover:bg-primary-50 hover:text-primary-700" aria-label="Notifikasi">
        <flux:icon.bell class="size-5" />
        @if ($this->belumDibaca > 0)
            <span class="absolute end-1 top-1 flex size-4 items-center justify-center rounded-full bg-red-600 text-[0.6rem] font-bold text-white">
                {{ min($this->belumDibaca, 9) }}{{ $this->belumDibaca > 9 ? '+' : '' }}
            </span>
        @endif
    </button>

    <flux:menu class="w-80">
        <div class="flex items-center justify-between px-2 py-1.5">
            <p class="text-sm font-semibold">Notifikasi</p>
            @if ($this->belumDibaca > 0)
                <button type="button" wire:click="tandaiSemua" class="text-xs text-primary-700 hover:underline">Tandai semua dibaca</button>
            @endif
        </div>
        <flux:menu.separator />

        @forelse ($this->terbaru as $notifikasi)
            <button type="button" wire:click="buka('{{ $notifikasi->id }}')" @class(['block w-full rounded-md px-2 py-2 text-start hover:bg-primary-50', 'bg-primary-50/60' => ! $notifikasi->read_at])>
                <p class="text-sm font-medium text-primary-900">{{ $notifikasi->data['judul'] ?? 'Notifikasi' }}</p>
                <p class="text-xs text-zinc-600">{{ $notifikasi->data['pesan'] ?? '' }}</p>
                <p class="mt-0.5 text-[0.7rem] text-zinc-400">{{ $notifikasi->created_at->diffForHumans() }}</p>
            </button>
        @empty
            <p class="px-2 py-4 text-center text-sm text-zinc-500">Belum ada notifikasi.</p>
        @endforelse
    </flux:menu>
</flux:dropdown>
