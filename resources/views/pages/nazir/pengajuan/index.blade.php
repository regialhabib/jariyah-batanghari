<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengajuan')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">Pengajuan</flux:heading>

    <div class="kartu">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
