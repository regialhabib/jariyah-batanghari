// Editor teks kaya (Trix) untuk Kelola Konten → Artikel.
// Integrasi Livewire ada di halaman (wire:ignore + input hidden + trix-change → $wire.set('isi', ...)).
import Trix from 'trix';

// Label toolbar berbahasa Indonesia.
Object.assign(Trix.config.lang, {
    attachFiles: 'Lampirkan file',
    bold: 'Tebal',
    bullets: 'Daftar berpoin',
    byte: 'Byte',
    bytes: 'Byte',
    captionPlaceholder: 'Tambahkan keterangan…',
    code: 'Kode',
    heading1: 'Subjudul',
    indent: 'Tambah indentasi',
    italic: 'Miring',
    link: 'Tautan',
    numbers: 'Daftar bernomor',
    outdent: 'Kurangi indentasi',
    quote: 'Kutipan',
    redo: 'Ulangi',
    remove: 'Hapus',
    strike: 'Coret',
    undo: 'Batalkan',
    unlink: 'Hapus tautan',
    url: 'URL',
    urlPlaceholder: 'Masukkan URL…',
});

// Judul di dalam isi artikel memakai <h2> (halaman detail sudah punya <h1>).
Trix.config.blockAttributes.heading1.tagName = 'h2';

// Unggah lampiran lewat editor dimatikan: gambar dikelola terpisah.
document.addEventListener('trix-file-accept', (event) => event.preventDefault());

// Sembunyikan tombol lampiran karena unggahan dimatikan.
document.addEventListener('trix-initialize', (event) => {
    event.target.toolbarElement?.querySelector('[data-trix-button-group="file-tools"]')?.remove();
});
