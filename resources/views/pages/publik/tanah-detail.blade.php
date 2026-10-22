<?php

use App\Models\TanahWakaf;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::publik')] class extends Component {
    public TanahWakaf $tanah;

    public function mount(TanahWakaf $tanah): void
    {
        abort_unless($tanah->is_published, 404);
        $this->tanah = $tanah;
    }

    public function render()
    {
        return $this->view()->title($this->tanah->nama);
    }
}; ?>

<div>
    <x-page-header :title="$tanah->nama" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Peta Wakaf', 'url' => route('peta')], ['label' => $tanah->nama]]" />

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-empty-state icon="wrench-screwdriver" title="Halaman sedang dibangun" />
    </div>
</div>
