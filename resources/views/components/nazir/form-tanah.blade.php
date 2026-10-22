@props([
    // [id => nama] kecamatan dan desa (desa sesuai kecamatan terpilih)
    'kecamatans' => [],
    'desas' => [],
])

{{--
    Field data tanah untuk pengajuan AIW. Dipakai di wizard (pages::nazir.pengajuan.buat) dan form ubah
    di detail pengajuan. Komponen Livewire induk wajib punya properti: nama, jenis_penggunaan, wakif_nama,
    wakif_nik, kecamatan_id (wire:model.live), desa_id, alamat, luas_m2.
--}}
<div {{ $attributes->class(['grid gap-4 sm:grid-cols-2']) }}>
    <div class="sm:col-span-2">
        <flux:input wire:model="nama" label="Nama tanah wakaf / peruntukan" placeholder="mis. Masjid Al-Ikhlas" maxlength="255" required />
    </div>

    <flux:select wire:model="jenis_penggunaan" label="Jenis penggunaan" required>
        <flux:select.option value="">Pilih jenis penggunaan</flux:select.option>
        @foreach (\App\Enums\JenisPenggunaan::opsi() as $nilai => $label)
            <flux:select.option value="{{ $nilai }}">{{ $label }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="luas_m2" label="Luas (m²)" type="number" min="1" step="0.01" placeholder="mis. 1200" required />

    <flux:input wire:model="wakif_nama" label="Nama wakif" maxlength="255" required />

    <flux:input wire:model="wakif_nik" label="NIK wakif (opsional)" inputmode="numeric" maxlength="16" placeholder="16 digit" description:trailing="Tidak ditampilkan di halaman publik." />

    <flux:select wire:model.live="kecamatan_id" label="Kecamatan" required>
        <flux:select.option value="">Pilih kecamatan</flux:select.option>
        @foreach ($kecamatans as $id => $nama)
            <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="desa_id" label="Desa/Kelurahan" :disabled="blank($desas)" required>
        <flux:select.option value="">{{ blank($desas) ? 'Pilih kecamatan terlebih dahulu' : 'Pilih desa/kelurahan' }}</flux:select.option>
        @foreach ($desas as $id => $nama)
            <flux:select.option value="{{ $id }}">{{ $nama }}</flux:select.option>
        @endforeach
    </flux:select>

    <div class="sm:col-span-2">
        <flux:textarea wire:model="alamat" label="Alamat / letak tanah" rows="2" maxlength="500" placeholder="Jalan, RT/RW, patokan lokasi" />
    </div>
</div>
