@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-800']) }} role="status">
        <flux:icon.check-circle variant="mini" class="mt-0.5 shrink-0 text-primary-600" />
        <span>{{ $status }}</span>
    </div>
@endif
