@props([
    'title' => null,
    'judul' => 'Selamat Datang di Jariyah Batang Hari',
    'deskripsi' => 'Silakan login untuk mengakses sistem sesuai dengan akun Anda.',
    'ilustrasi' => 'lanskap',
    'lebar' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-krem font-sans text-zinc-800 antialiased">
        <div class="relative flex min-h-svh items-center justify-center px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
            <x-ornamen-daun class="pointer-events-none absolute -left-6 top-6 hidden size-32 text-primary-100 lg:block" />
            <x-ornamen-daun class="pointer-events-none absolute -right-4 bottom-6 hidden size-28 rotate-180 text-primary-100 lg:block" />

            <div @class([
                'relative grid w-full overflow-hidden rounded-3xl bg-white shadow-kartu ring-1 ring-primary-100/70',
                'max-w-6xl lg:grid-cols-[5fr_7fr]' => $lebar,
                'max-w-5xl lg:grid-cols-2' => ! $lebar,
            ])>
                {{-- Panel kiri hijau: logo, sambutan, ilustrasi. Di layar kecil tampil ringkas di atas form. --}}
                <aside class="relative isolate flex flex-col overflow-hidden bg-linear-to-br from-primary-700 via-primary-800 to-primary-950 px-6 py-6 text-white sm:px-8 lg:min-h-[36rem] lg:px-10 lg:py-10">
                    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_85%_15%,rgb(139_195_74/0.28),transparent_45%)]"></div>
                    <x-ornamen-daun class="pointer-events-none absolute -right-6 -top-6 -z-10 size-36 text-white/10" />

                    <a href="{{ route('beranda') }}" class="self-start" wire:navigate>
                        <x-logo putih />
                        <span class="sr-only">{{ config('app.name', 'Jariyah Batang Hari') }}</span>
                    </a>

                    <div class="mt-5 lg:mt-14">
                        <h2 class="text-xl font-bold leading-snug sm:text-2xl lg:text-3xl">{{ $judul }}</h2>
                        <div class="mt-3 hidden h-1 w-14 rounded-full bg-daun sm:block"></div>
                        <p class="mt-2 text-sm leading-relaxed text-primary-100 sm:mt-4 lg:text-base">{{ $deskripsi }}</p>
                    </div>

                    <div class="relative -mx-10 -mb-10 mt-auto hidden pt-10 lg:block" aria-hidden="true">
                        @if ($ilustrasi === 'masjid')
                            <svg viewBox="0 0 400 260" class="w-full" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="300" cy="60" r="28" fill="#8bc34a" fill-opacity=".35" />
                                <path d="M0 215c70-22 140-26 200-18s130 4 200-14v77H0z" fill="#fff" fill-opacity=".08" />
                                {{-- Menara kiri & kanan --}}
                                <g fill="#fff" fill-opacity=".9">
                                    <rect x="78" y="96" width="20" height="130" rx="2" />
                                    <path d="M76 98c0-14 7-22 12-28 5 6 12 14 12 28z" />
                                    <rect x="87" y="56" width="2" height="16" />
                                    <rect x="302" y="96" width="20" height="130" rx="2" />
                                    <path d="M300 98c0-14 7-22 12-28 5 6 12 14 12 28z" />
                                    <rect x="311" y="56" width="2" height="16" />
                                </g>
                                {{-- Bangunan utama + kubah --}}
                                <path d="M118 150h164v80H118z" fill="#fff" fill-opacity=".95" />
                                <path d="M140 152c0-46 34-62 60-82 26 20 60 36 60 82z" fill="#fff" />
                                <rect x="199" y="34" width="2" height="38" fill="#fff" />
                                <path d="M206 30a8 8 0 1 1-6-12 6.5 6.5 0 1 0 6 12z" fill="#8bc34a" />
                                <path d="M118 150h164v8H118z" fill="#8bc34a" fill-opacity=".6" />
                                {{-- Pintu & jendela --}}
                                <g fill="#1a5530">
                                    <path d="M186 230v-34a14 14 0 0 1 28 0v34z" />
                                    <path d="M138 200v-18a8 8 0 0 1 16 0v18zM162 200v-18a8 8 0 0 1 16 0v18zM222 200v-18a8 8 0 0 1 16 0v18zM246 200v-18a8 8 0 0 1 16 0v18z" />
                                    <rect x="84" y="120" width="8" height="14" rx="4" />
                                    <rect x="308" y="120" width="8" height="14" rx="4" />
                                </g>
                                {{-- Pepohonan --}}
                                <g fill="#8bc34a" fill-opacity=".75">
                                    <circle cx="40" cy="208" r="18" />
                                    <circle cx="58" cy="216" r="13" />
                                    <circle cx="352" cy="210" r="17" />
                                    <circle cx="370" cy="218" r="12" />
                                </g>
                                <path d="M0 226c80-12 150-10 210-4s130 8 190-2v40H0z" fill="#0a2614" fill-opacity=".45" />
                            </svg>
                        @else
                            <svg viewBox="0 0 400 220" class="w-full" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="310" cy="58" r="30" fill="#8bc34a" fill-opacity=".35" />
                                <path d="M0 140c60-38 120-30 180-16s120 18 220-24v120H0z" fill="#fff" fill-opacity=".07" />
                                {{-- Siluet masjid di kejauhan --}}
                                <g fill="#fff" fill-opacity=".85">
                                    <rect x="236" y="96" width="9" height="56" rx="1" />
                                    <path d="M235 98c0-7 3-10 5.5-13 2.5 3 5.5 6 5.5 13z" />
                                    <path d="M250 120h56v34h-56z" />
                                    <path d="M258 121c0-20 14-26 20-34 6 8 20 14 20 34z" />
                                    <rect x="277.4" y="74" width="1.2" height="14" />
                                </g>
                                <path d="M0 170c70-26 150-22 220-6s120 10 180-6v62H0z" fill="#fff" fill-opacity=".12" />
                                {{-- Pepohonan --}}
                                <g fill="#8bc34a" fill-opacity=".7">
                                    <circle cx="60" cy="160" r="16" />
                                    <circle cx="80" cy="166" r="11" />
                                    <circle cx="150" cy="168" r="13" />
                                    <circle cx="350" cy="160" r="14" />
                                </g>
                                <path d="M0 192c90-16 170-14 240-6s110 6 160-2v36H0z" fill="#0a2614" fill-opacity=".4" />
                            </svg>
                        @endif
                    </div>
                </aside>

                {{-- Kolom kanan: form --}}
                <main class="flex items-center px-5 py-8 sm:px-10 sm:py-10 lg:px-12">
                    <div @class(['mx-auto flex w-full flex-col gap-6', 'max-w-2xl' => $lebar, 'max-w-md' => ! $lebar])>
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
