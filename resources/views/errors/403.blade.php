@include('errors.layout', [
    'kode' => 403,
    'title' => 'Akses Ditolak',
    'ikon' => 'lock-closed',
    'pesan' => 'Anda tidak memiliki hak untuk membuka halaman ini. Pastikan Anda masuk dengan akun yang sesuai.',
])
