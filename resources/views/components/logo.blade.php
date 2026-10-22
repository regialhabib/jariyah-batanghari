@props([
    'putih' => false,
])

{{-- Logo lengkap "JARIYAH / BATANG HARI" untuk header, footer, dan halaman auth. --}}
<span {{ $attributes->class(['inline-flex items-center gap-2.5']) }}>
    <x-app-logo-icon @class(['h-9 w-auto shrink-0', 'text-white' => $putih, 'text-primary-700' => ! $putih]) />
    <span class="flex flex-col leading-none">
        <span @class(['text-xl font-bold tracking-wide', 'text-white' => $putih, 'text-primary-800' => ! $putih])>JARIYAH</span>
        <span @class(['text-[0.62rem] font-semibold tracking-[0.2em]', 'text-primary-100' => $putih, 'text-primary-600' => ! $putih])>BATANG HARI</span>
    </span>
</span>
