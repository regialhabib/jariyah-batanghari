<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Daftar Nazir')] class extends Component {
    //
}; ?>

<div>
    <x-page-header title="Daftar Nazir" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Daftar Nazir']]" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
