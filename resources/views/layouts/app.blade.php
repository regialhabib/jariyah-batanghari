{{-- Layout default semua halaman dashboard (Nazir & petugas), termasuk halaman Profil. --}}
<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="mx-auto w-full max-w-7xl">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
