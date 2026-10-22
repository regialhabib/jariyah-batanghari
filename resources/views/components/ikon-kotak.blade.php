@props([
    'icon',
    'size' => 'md',
    'variant' => 'solid',
])

{{-- Ikon heroicon di dalam kotak hijau membulat (gaya kartu di mockup). --}}
<span {{ $attributes->class([
    'flex shrink-0 items-center justify-center rounded-xl',
    'size-9' => $size === 'sm',
    'size-12' => $size === 'md',
    'size-14' => $size === 'lg',
    'bg-primary-700 text-white' => $variant === 'solid',
    'bg-primary-50 text-primary-700' => $variant === 'soft',
]) }}>
    <flux:icon :name="$icon" @class(['size-5' => $size === 'sm', 'size-6' => $size === 'md', 'size-7' => $size === 'lg']) />
</span>
