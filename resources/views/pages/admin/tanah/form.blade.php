<?php

use App\Models\TanahWakaf;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Form Data Wakaf')] class extends Component {
    public ?TanahWakaf $tanah = null;

    public function mount(?TanahWakaf $tanah = null): void
    {
        $this->tanah = $tanah?->exists ? $tanah : null;
        $this->tanah ? $this->authorize('update', $this->tanah) : $this->authorize('create', TanahWakaf::class);
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">{{ $tanah ? 'Ubah' : 'Tambah' }} Data Wakaf</flux:heading>

    <div class="kartu">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
