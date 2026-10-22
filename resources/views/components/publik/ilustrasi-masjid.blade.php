{{--
    Ilustrasi masjid berkubah hijau dengan pohon kelapa dan perbukitan (pengganti foto sampai
    foto resmi tersedia). Dekoratif saja, jadi disembunyikan dari pembaca layar.
--}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 360" aria-hidden="true" focusable="false" {{ $attributes }}>
    <defs>
        <linearGradient id="ilustrasi-kubah" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#4fa266" />
            <stop offset="1" stop-color="#1a5530" />
        </linearGradient>
        <linearGradient id="ilustrasi-langit" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#d6ecdb" />
            <stop offset="1" stop-color="#f7f8f3" />
        </linearGradient>
    </defs>

    {{-- Latar --}}
    <circle cx="270" cy="180" r="165" fill="url(#ilustrasi-langit)" />
    <path d="M0 262c70-46 140-56 210-30s150 10 270-36v164H0Z" fill="#afd9b9" opacity=".7" />

    {{-- Menara --}}
    <rect x="118" y="104" width="24" height="182" fill="#fff" stroke="#afd9b9" stroke-width="2" />
    <rect x="111" y="146" width="38" height="7" rx="2" fill="#1f6b3a" />
    <rect x="111" y="214" width="38" height="6" rx="2" fill="#2e8b4e" />
    <path d="M118 104c0-17 24-17 24 0Z" fill="url(#ilustrasi-kubah)" />
    <path d="M130 86v-8" stroke="#1f6b3a" stroke-width="2" stroke-linecap="round" />
    <path d="M126 168a4 4 0 0 1 8 0v18h-8Z" fill="#afd9b9" />

    {{-- Bangunan utama --}}
    <rect x="168" y="200" width="226" height="88" fill="#fff" stroke="#afd9b9" stroke-width="2" />
    <rect x="164" y="196" width="234" height="8" rx="2" fill="#1f6b3a" />
    <rect x="230" y="184" width="100" height="14" fill="#f7f8f3" stroke="#afd9b9" stroke-width="2" />
    <path d="M234 186c-6-40 26-66 46-76 20 10 52 36 46 76Z" fill="url(#ilustrasi-kubah)" />
    <path d="M252 150c4-14 14-24 24-30" stroke="#7fbf8f" stroke-width="4" stroke-linecap="round" fill="none" opacity=".7" />
    <path d="M280 110V92" stroke="#1f6b3a" stroke-width="2.5" stroke-linecap="round" />
    <path d="M284 84a6 6 0 1 1-6-7 5 5 0 1 0 6 7Z" fill="#f59e0b" />

    {{-- Kubah kecil di sudut --}}
    <path d="M172 196c0-18 30-18 30 0Z" fill="url(#ilustrasi-kubah)" />
    <path d="M360 196c0-18 30-18 30 0Z" fill="url(#ilustrasi-kubah)" />

    {{-- Jendela lengkung & pintu --}}
    <g fill="#afd9b9">
        <path d="M188 266v-22a9 9 0 0 1 18 0v22Z" />
        <path d="M222 266v-22a9 9 0 0 1 18 0v22Z" />
        <path d="M320 266v-22a9 9 0 0 1 18 0v22Z" />
        <path d="M354 266v-22a9 9 0 0 1 18 0v22Z" />
    </g>
    <path d="M264 288v-36a16 16 0 0 1 32 0v36Z" fill="#1f6b3a" />

    {{-- Pohon kelapa kiri --}}
    <path d="M66 300c6-40 14-76 30-108" stroke="#8a6a43" stroke-width="7" stroke-linecap="round" fill="none" />
    <g fill="#2e8b4e">
        <path d="M96 192c-20-16-46-14-60 2 22-8 40-6 60-2Z" />
        <path d="M96 192c-6-22-28-34-48-30 20 6 34 16 48 30Z" />
        <path d="M96 192c8-22 30-30 50-24-20 4-36 12-50 24Z" />
        <path d="M96 192c22-8 44 0 54 18-18-10-36-14-54-18Z" />
        <path d="M96 192c-12 12-16 30-10 46 0-18 4-32 10-46Z" />
    </g>

    {{-- Pohon kelapa kanan --}}
    <path d="M428 302c-4-36-12-68-26-98" stroke="#8a6a43" stroke-width="7" stroke-linecap="round" fill="none" />
    <g fill="#1f6b3a">
        <path d="M402 204c20-16 46-12 58 4-20-8-38-8-58-4Z" />
        <path d="M402 204c6-22 28-32 48-26-20 4-34 14-48 26Z" />
        <path d="M402 204c-8-22-30-30-50-22 20 4 36 10 50 22Z" />
        <path d="M402 204c-22-6-42 4-50 22 16-12 34-16 50-22Z" />
        <path d="M402 204c12 12 14 30 8 46 0-18-4-32-8-46Z" />
    </g>

    {{-- Semak & tanah depan --}}
    <g fill="#4fa266">
        <circle cx="160" cy="292" r="16" />
        <circle cx="182" cy="296" r="12" />
        <circle cx="384" cy="294" r="15" />
        <circle cx="404" cy="298" r="11" />
    </g>
    <path d="M0 296c120-24 300-20 480 4v60H0Z" fill="#2e8b4e" />
    <path d="M0 322c90-22 190-12 280 6s140 4 200-10v42H0Z" fill="#1f6b3a" />
</svg>
