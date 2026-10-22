// Editor teks kaya (Trix) untuk Kelola Konten. Diisi pada Fase 7.
import 'trix';

// Unggah lampiran lewat editor dimatikan: gambar dikelola terpisah.
document.addEventListener('trix-file-accept', (event) => event.preventDefault());
