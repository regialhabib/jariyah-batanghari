{{--
    Bilah tombol simpan yang menempel di bawah layar untuk form panjang, sehingga tombol Simpan
    selalu terlihat. Margin negatif menyamakan lebar dengan padding <flux:main> (p-6 lg:p-8).
--}}
<div {{ $attributes->class(['sticky bottom-0 z-10 -mx-6 flex flex-wrap items-center justify-end gap-x-4 gap-y-2 border-t border-primary-100 bg-white/90 px-6 py-3 backdrop-blur lg:-mx-8 lg:px-8']) }}>
    <p wire:dirty class="me-auto flex items-center gap-1.5 text-sm text-amber-700">
        <flux:icon.exclamation-circle variant="micro" class="size-4" /> Ada perubahan yang belum disimpan
    </p>
    <div class="flex gap-2">{{ $slot }}</div>
</div>
