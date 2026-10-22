@props([
    'label' => null,
    'keterangan' => null,
    'accept' => null,
])

{{--
    Pengganti <input type="file"> bawaan peramban (yang teksnya berbahasa Inggris): kotak unggah
    berbahasa Indonesia dengan nama berkas terpilih, seret-lepas, dan progres unggah Livewire.
    Pakai: <x-admin.unggah-berkas wire:model="berkas" label="Berkas" keterangan="PDF, maks 5 MB." accept=".pdf" />
--}}
@php
    $model = $attributes->wire('model')->value();
    $id = 'unggah-'.\Illuminate\Support\Str::slug($model);
@endphp

<div {{ $attributes->whereDoesntStartWith('wire:model')->class('space-y-2') }}
    x-data="{ nama: '', progres: null, seret: false }"
    x-init="$wire.$watch(@js($model), (nilai) => { if (! nilai) nama = '' })"
    x-on:livewire-upload-start="progres = 0"
    x-on:livewire-upload-progress="progres = $event.detail.progress"
    x-on:livewire-upload-finish="progres = null"
    x-on:livewire-upload-error="progres = null; nama = ''">
    @if ($label)
        <flux:label for="{{ $id }}">{{ $label }}</flux:label>
    @endif

    <label for="{{ $id }}"
        x-on:dragover.prevent="seret = true" x-on:dragleave="seret = false"
        x-on:drop.prevent="seret = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change', { bubbles: true }))"
        :class="seret ? 'border-primary-500 bg-primary-50' : 'border-primary-200 bg-primary-50/40'"
        class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed px-4 py-3 transition hover:border-primary-400 hover:bg-primary-50 focus-within:ring-2 focus-within:ring-primary-500/40">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-white text-primary-700 ring-1 ring-primary-100">
            <flux:icon.arrow-up-tray class="size-5" x-show="! nama" />
            <flux:icon.document-check class="size-5" x-show="nama" x-cloak />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-medium text-primary-900" x-text="nama || 'Pilih berkas atau seret ke sini'">Pilih berkas atau seret ke sini</span>
            <span class="block text-xs text-zinc-500" x-show="progres === null" x-text="nama ? 'Klik untuk mengganti berkas' : 'Klik untuk memilih dari perangkat'">Klik untuk memilih dari perangkat</span>
            <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-primary-100" x-show="progres !== null" x-cloak role="progressbar" aria-label="Progres unggah" :aria-valuenow="progres">
                <span class="block h-full rounded-full bg-primary-600 transition-all" :style="{ width: progres + '%' }"></span>
            </span>
        </span>

        <span class="hidden shrink-0 rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-primary-700 ring-1 ring-primary-200 sm:inline">Telusuri</span>

        <input id="{{ $id }}" type="file" class="sr-only" x-ref="input" {{ $attributes->whereStartsWith('wire:model') }}
            @if ($accept) accept="{{ $accept }}" @endif
            x-on:change="nama = $event.target.files[0]?.name ?? ''">
    </label>

    @if ($keterangan)
        <flux:description>{{ $keterangan }}</flux:description>
    @endif
    <flux:error :name="$model" />
</div>
