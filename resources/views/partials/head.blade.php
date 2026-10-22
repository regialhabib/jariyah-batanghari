<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="{{ $description ?? 'Jariyah Batang Hari — sistem informasi pengelolaan dan pemantauan tanah wakaf Kantor Pertanahan Kabupaten Batang Hari: informasi wakaf, persyaratan, peta sebaran, dan pengajuan sertifikat tanah wakaf secara daring.' }}" />
<meta name="theme-color" content="#1F6B3A" />
<meta property="og:site_name" content="{{ config('app.name') }}" />
<meta property="og:title" content="{{ filled($title ?? null) ? $title : config('app.name') }}" />
<meta property="og:type" content="website" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('head')
