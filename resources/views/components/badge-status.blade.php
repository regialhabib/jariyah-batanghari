@props([
    // Enum apa pun yang punya label() dan warna(): StatusPengajuan, StatusSertifikat, StatusValidasiDokumen.
    'status',
])

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium', $status->warna()]) }}>
    {{ $status->label() }}
</span>
