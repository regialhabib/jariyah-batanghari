<?php

use App\Models\Pengajuan;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Pengajuan')] class extends Component {
    public Pengajuan $pengajuan;

    public function mount(Pengajuan $pengajuan): void
    {
        $this->authorize('view', $pengajuan);
        $this->pengajuan = $pengajuan;
    }
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">Detail Pengajuan</flux:heading>

    <div class="kartu">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
