<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kelola Artikel')] class extends Component {
    //
}; ?>

<div class="space-y-6">
    <flux:heading size="xl" level="1">Kelola Artikel</flux:heading>

    <div class="kartu">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
