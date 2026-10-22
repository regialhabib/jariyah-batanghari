<?php

use App\Models\Artikel;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::publik')] class extends Component {
    public Artikel $artikel;

    public function mount(Artikel $artikel): void
    {
        abort_unless($artikel->is_published, 404);
        $this->artikel = $artikel;
    }

    public function render()
    {
        return $this->view()->title($this->artikel->judul);
    }
}; ?>

<div>
    <x-page-header :title="$artikel->judul" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Informasi Wakaf', 'url' => route('informasi.index')], ['label' => $artikel->judul]]" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
