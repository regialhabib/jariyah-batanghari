<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::publik'), Title('Tentang Jariyah')] class extends Component {
    //
}; ?>

<div>
    <x-page-header title="Tentang Jariyah" :breadcrumb="[['label' => 'Beranda', 'url' => route('beranda')], ['label' => 'Tentang Jariyah']]" />

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
        {{-- Pengantar --}}
        <section class="grid items-center gap-8 md:grid-cols-5">
            <div class="kartu overflow-hidden bg-primary-50 md:col-span-2">
                <x-publik.ilustrasi-masjid class="h-auto w-full" />
            </div>
            <div class="md:col-span-3">
                <h2 class="text-2xl font-bold text-primary-900">Apa itu Jariyah Batang Hari?</h2>
                <p class="mt-3 leading-relaxed text-zinc-700">{{ setting('tentang_deskripsi') }}</p>
            </div>
        </section>

        {{-- Tujuan, Manfaat, Instansi --}}
        <section class="grid gap-5 md:grid-cols-3" aria-label="Tujuan, manfaat, dan instansi terkait">
            <article class="kartu p-6">
                <x-ikon-kotak icon="light-bulb" variant="soft" />
                <h3 class="mt-4 text-lg font-semibold text-primary-900">Tujuan</h3>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ setting('tentang_tujuan') }}</p>
            </article>

            <article class="kartu p-6">
                <x-ikon-kotak icon="sparkles" variant="soft" />
                <h3 class="mt-4 text-lg font-semibold text-primary-900">Manfaat</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-zinc-600">
                    @foreach (setting_list('tentang_manfaat') as $manfaat)
                        <li class="flex gap-2">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-primary-600" aria-hidden="true"></span>
                            <span>{{ $manfaat }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="kartu p-6">
                <x-ikon-kotak icon="user-group" variant="soft" />
                <h3 class="mt-4 text-lg font-semibold text-primary-900">Instansi Terkait</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-zinc-600">
                    @foreach (setting_list('tentang_instansi') as $instansi)
                        <li class="flex gap-2">
                            <flux:icon.chevron-right variant="micro" class="mt-0.5 shrink-0 text-primary-600" />
                            <span>{{ $instansi }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>

        {{-- Banner penutup --}}
        <section class="relative overflow-hidden rounded-2xl bg-linear-to-r from-primary-800 to-primary-600 px-6 py-7 text-white shadow-kartu sm:px-8">
            <x-ornamen-daun class="pointer-events-none absolute -bottom-6 end-2 size-28 text-white/15" />
            <div class="relative flex items-center gap-4">
                <span class="hidden size-12 shrink-0 items-center justify-center rounded-xl bg-white/15 sm:flex">
                    <flux:icon.hand-raised class="size-6" />
                </span>
                <p class="text-lg font-semibold leading-snug sm:text-xl">
                    Bersama Jariyah Batang Hari,<br>
                    Wakaf Lebih Mudah, Aman dan Terdata.
                </p>
            </div>
        </section>
    </div>
</div>
