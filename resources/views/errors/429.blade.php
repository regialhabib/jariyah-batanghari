@include('errors.layout', [
    'kode' => 429,
    'title' => 'Terlalu Banyak Permintaan',
    'ikon' => 'hand-raised',
    'pesan' => 'Anda melakukan terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar lalu coba lagi.',
])
