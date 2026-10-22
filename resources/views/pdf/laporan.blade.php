<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Tanah Wakaf</title>
    <style>
        @page { margin: 1.4cm 1.2cm 1.4cm 1.2cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1f2937; }
        .kop { width: 100%; border-bottom: 3px double #123f22; padding-bottom: 6px; margin-bottom: 12px; text-align: center; }
        .kop .instansi { font-size: 11pt; font-weight: bold; letter-spacing: .3px; }
        .kop .kantor { font-size: 12.5pt; font-weight: bold; margin-top: 2px; }
        .kop .alamat { font-size: 8pt; margin-top: 3px; color: #374151; }
        h1 { font-size: 12pt; text-align: center; margin: 4px 0 2px; text-transform: uppercase; }
        .sub { text-align: center; font-size: 8.5pt; color: #4b5563; margin-bottom: 10px; }
        h2 { font-size: 9.5pt; margin: 14px 0 5px; color: #123f22; }
        table { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #9ca3af; padding: 3px 4px; vertical-align: top; }
        table.data th { background: #dcefe2; font-weight: bold; text-align: center; }
        table.data tr.jumlah td { font-weight: bold; background: #f3f4f6; }
        .kanan { text-align: right; }
        .tengah { text-align: center; }
        .ringkas td { padding: 4px 6px; border: 1px solid #d1d5db; }
        .ringkas .angka { font-size: 11pt; font-weight: bold; color: #123f22; }
        .ttd { width: 100%; margin-top: 24px; page-break-inside: avoid; }
        .ttd td { vertical-align: top; }
        .kosong { text-align: center; color: #6b7280; padding: 10px; }
        .footer { position: fixed; bottom: -0.9cm; left: 0; right: 0; font-size: 7pt; color: #6b7280; }
    </style>
</head>
<body>
    <div class="footer">Dicetak dari Jariyah Batang Hari pada {{ $dicetak->translatedFormat('d F Y H:i') }} WIB oleh {{ $pencetak?->name }}</div>

    <div class="kop">
        <div class="instansi">KEMENTERIAN AGRARIA DAN TATA RUANG / BADAN PERTANAHAN NASIONAL</div>
        <div class="kantor">KANTOR PERTANAHAN KABUPATEN BATANG HARI</div>
        <div class="alamat">
            {{ setting('alamat', 'Muara Bulian, Kabupaten Batang Hari, Jambi') }}
            @if (setting('telepon')) · Telp. {{ setting('telepon') }} @endif
            @if (setting('email')) · {{ setting('email') }} @endif
        </div>
    </div>

    <h1>Laporan Data Tanah Wakaf</h1>
    <div class="sub">
        {{ implode(' · ', $filter->uraian()) }}<br>
        Per tanggal {{ $dicetak->translatedFormat('d F Y') }}
    </div>

    <table class="ringkas">
        <tr>
            <td>Total Tanah Wakaf<br><span class="angka">{{ number_format($ringkasan['total'], 0, ',', '.') }}</span> bidang</td>
            <td>Sudah Bersertifikat<br><span class="angka">{{ number_format($ringkasan['sudah'], 0, ',', '.') }}</span> bidang</td>
            <td>Dalam Proses<br><span class="angka">{{ number_format($ringkasan['proses'], 0, ',', '.') }}</span> bidang</td>
            <td>Belum Bersertifikat<br><span class="angka">{{ number_format($ringkasan['belum'], 0, ',', '.') }}</span> bidang</td>
            <td>Total Luas<br><span class="angka">{{ format_luas($ringkasan['luas_m2']) }}</span></td>
            <td>Sebaran<br><span class="angka">{{ $ringkasan['kecamatan'] }}</span> kec. / <span class="angka">{{ $ringkasan['desa'] }}</span> desa</td>
        </tr>
    </table>

    <h2>A. Rekapitulasi per Kecamatan</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 4%">No</th>
                <th>Kecamatan</th>
                <th style="width: 13%">Sudah Bersertifikat</th>
                <th style="width: 11%">Dalam Proses</th>
                <th style="width: 13%">Belum Bersertifikat</th>
                <th style="width: 10%">Total Bidang</th>
                <th style="width: 14%">Total Luas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekap as $r)
                <tr>
                    <td class="tengah">{{ $loop->iteration }}</td>
                    <td>{{ $r['kecamatan'] }}</td>
                    <td class="kanan">{{ $r['sudah'] }}</td>
                    <td class="kanan">{{ $r['proses'] }}</td>
                    <td class="kanan">{{ $r['belum'] }}</td>
                    <td class="kanan">{{ $r['total'] }}</td>
                    <td class="kanan">{{ format_luas($r['luas_m2']) }}</td>
                </tr>
            @endforeach
            <tr class="jumlah">
                <td colspan="2" class="tengah">JUMLAH</td>
                <td class="kanan">{{ $rekap->sum('sudah') }}</td>
                <td class="kanan">{{ $rekap->sum('proses') }}</td>
                <td class="kanan">{{ $rekap->sum('belum') }}</td>
                <td class="kanan">{{ $rekap->sum('total') }}</td>
                <td class="kanan">{{ format_luas($rekap->sum('luas_m2')) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>B. Rekapitulasi Pengajuan per Status</h2>
    <table class="data">
        <thead>
            <tr>
                @foreach (\App\Enums\StatusPengajuan::cases() as $s)
                    <th>{{ $s->label() }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach (\App\Enums\StatusPengajuan::cases() as $s)
                    <td class="tengah">{{ $rekapPengajuan[$s->value] ?? 0 }}</td>
                @endforeach
                <td class="tengah"><strong>{{ array_sum($rekapPengajuan) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h2>C. Daftar Tanah Wakaf</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 7%">Kode</th>
                <th>Nama Tanah Wakaf</th>
                <th style="width: 10%">Kecamatan</th>
                <th style="width: 10%">Desa/Kel.</th>
                <th style="width: 7%">Luas</th>
                <th style="width: 9%">Penggunaan</th>
                <th style="width: 11%">Nazir</th>
                <th style="width: 10%">Wakif</th>
                <th style="width: 9%">Status</th>
                <th style="width: 11%">No. AIW / Sertifikat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tanahs as $t)
                @php $b = \App\Support\FilterLaporan::baris($t); @endphp
                <tr>
                    <td class="tengah">{{ $loop->iteration }}</td>
                    <td>{{ $b['kode'] }}</td>
                    <td>{{ $b['nama'] }}</td>
                    <td>{{ $b['kecamatan'] }}</td>
                    <td>{{ $b['desa'] }}</td>
                    <td class="kanan">{{ format_luas($b['luas_m2']) }}</td>
                    <td>{{ $b['jenis_penggunaan'] }}</td>
                    <td>{{ $b['nazir'] ?? '—' }}</td>
                    <td>{{ $b['wakif'] }}</td>
                    <td>{{ $b['status'] }}</td>
                    <td>
                        {{ $b['nomor_aiw'] ?? '—' }}
                        @if ($b['nomor_sertifikat'])<br>{{ $b['nomor_sertifikat'] }}@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="kosong">Tidak ada data tanah wakaf sesuai filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td style="width: 62%"></td>
            <td>
                Muara Bulian, {{ $dicetak->translatedFormat('d F Y') }}<br>
                Kepala Kantor Pertanahan<br>
                Kabupaten Batang Hari
                <br><br><br><br><br>
                ( ........................................................ )<br>
                NIP. ...................................................
            </td>
        </tr>
    </table>
</body>
</html>
