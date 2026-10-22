import L from 'leaflet';

// Geoman (dimuat dinamis di mode `pilih`) memakai global `L`.
window.L = L;

/**
 * Komponen peta Leaflet (Alpine): `petaWakaf`.
 * Kontrak lengkap + contoh: docs/komponen-peta.md. Biasanya dipakai lewat <x-peta> / <x-peta-pilih>.
 *
 * Markup minimal:
 *   <div wire:ignore x-data="petaWakaf({ mode: 'data', data: [...] })" class="relative isolate h-96">
 *       <div x-ref="peta" class="absolute inset-0"></div>
 *   </div>
 *
 * Opsi:
 *   mode         'publik' (ambil GeoJSON dari `url`) | 'data' (array `data`) | 'pilih' (pemilih lokasi form)
 *   url          endpoint GeoJSON (mode publik), mis. route('peta.data')
 *   filter       filter awal mode publik: { q, kecamatan, jenis, status }
 *   event        nama event window untuk filter baru (default 'peta-filter'; detail = { filter: {...} })
 *   data         array objek TanahWakaf::dataPublik() (atau Feature GeoJSON) untuk mode data
 *   lat, lng     mode pilih: nilai/`$wire.entangle(...)` koordinat
 *   batas        mode pilih: nilai/`$wire.entangle(...)` polygon GeoJSON; jika kunci ini ada, alat gambar polygon aktif
 *   pusat, zoom  tampilan awal (default [-1.70, 103.15], 10)
 *   legenda      tampilkan legenda status (default true, kecuali mode pilih)
 *   scrollZoom   zoom dengan roda mouse (default true)
 *   tombolDetail tampilkan tombol "Lihat Detail" di popup (default true)
 *   batasWilayah URL GeoJSON garis batas kabupaten (opsional)
 *   cssGeoman    URL CSS Geoman (mode pilih; dimuat otomatis jika belum ada di <head>)
 *
 * Event window yang didengar: `peta-filter` (atau `event`), `peta-pindah` ({ lat, lng, zoom }).
 * Event yang dikirim (bubbling dari elemen komponen): `peta-dimuat` ({ jumlah, ringkasan }).
 */

const WARNA = { sudah: '#2E8B4E', belum: '#F59E0B', proses: '#3B82F6' };
const LEGENDA = [
    ['sudah', 'Sudah Bersertifikat'],
    ['belum', 'Belum Bersertifikat'],
    ['proses', 'Dalam Proses'],
];
const HIJAU_TUA = '#1F6B3A';
const PUSAT_DEFAULT = [-1.7, 103.15];
const ZOOM_DEFAULT = 10;
const ZOOM_TITIK = 16;

const BASEMAP = {
    peta: {
        label: 'Peta',
        buat: () =>
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
            }),
    },
    satelit: {
        label: 'Satelit',
        buat: () =>
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                maxNativeZoom: 18,
                attribution: 'Citra &copy; Esri &mdash; Sumber: Esri, Maxar, Earthstar Geographics, dan GIS User Community',
            }),
    },
};

const angka = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

function esc(nilai) {
    return String(nilai ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

/** Hanya URL http(s) atau path relatif yang boleh masuk ke href/src. */
function urlAman(nilai) {
    const s = String(nilai ?? '').trim();

    return /^(https?:\/\/|\/(?!\/))/i.test(s) ? s : null;
}

function warnaAman(t) {
    return /^#[0-9a-f]{3,8}$/i.test(t.warna ?? '') ? t.warna : (WARNA[t.status] ?? HIJAU_TUA);
}

function bulat7(n) {
    return Math.round(Number(n) * 1e7) / 1e7;
}

function koordinatValid(lat, lng) {
    const a = parseFloat(lat);
    const b = parseFloat(lng);

    return Number.isFinite(a) && Number.isFinite(b) && Math.abs(a) <= 90 && Math.abs(b) <= 180;
}

/** Terima objek dataPublik() atau Feature GeoJSON; kembalikan objek datar dengan lat/lng angka. */
function normalisasi(item) {
    if (!item || typeof item !== 'object') return null;

    let t = item;
    if (item.type === 'Feature') {
        const [lng, lat] = item.geometry?.coordinates ?? [];
        t = { ...item.properties, lat: item.properties?.lat ?? lat, lng: item.properties?.lng ?? lng };
    }

    return koordinatValid(t.lat, t.lng) ? { ...t, lat: parseFloat(t.lat), lng: parseFloat(t.lng) } : null;
}

function isiPopup(t, tombolDetail) {
    const foto = urlAman(t.foto);
    const url = urlAman(t.url);
    const warna = warnaAman(t);
    const baris = [
        ['Nazir', t.nazir],
        ['Desa', t.desa],
        ['Kecamatan', t.kecamatan],
        ['Luas', t.luas_m2 != null ? `${angka.format(t.luas_m2)} m²` : null],
    ];

    return `
        <div style="width:15rem">
            ${foto ? `<img src="${esc(foto)}" alt="Foto ${esc(t.nama)}" loading="lazy" style="display:block;width:100%;height:8rem;object-fit:cover;border-radius:.5rem;margin-bottom:.5rem">` : ''}
            <p style="margin:0;font-weight:600;font-size:.9rem;line-height:1.3;color:#123f22">${esc(t.nama)}</p>
            ${t.kode ? `<p style="margin:.1rem 0 0;font-size:.7rem;color:#737373">${esc(t.kode)}</p>` : ''}
            <table style="margin-top:.5rem;font-size:.75rem;line-height:1.5;color:#404040;border-collapse:collapse">
                ${baris
                    .map(([label, nilai]) => `<tr><td style="padding:0 .5rem 0 0;color:#737373;vertical-align:top">${label}</td><td style="padding:0;font-weight:500">${esc(nilai ?? '-')}</td></tr>`)
                    .join('')}
                <tr><td style="padding:0 .5rem 0 0;color:#737373">Status</td><td style="padding:0;font-weight:600;color:${warna}">
                    <span style="display:inline-block;width:.55rem;height:.55rem;border-radius:9999px;background:${warna};margin-right:.25rem"></span>${esc(t.status_label ?? t.status ?? '-')}
                </td></tr>
            </table>
            ${
                tombolDetail && url
                    ? `<a href="${esc(url)}" wire:navigate style="display:block;margin-top:.75rem;padding:.45rem .75rem;border-radius:.5rem;background:${HIJAU_TUA};color:#fff;text-align:center;font-size:.75rem;font-weight:600;text-decoration:none">Lihat Detail</a>`
                    : ''
            }
        </div>`;
}

function ikonPin(warna = HIJAU_TUA) {
    return L.divIcon({
        className: '',
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 40" width="30" height="40" style="filter:drop-shadow(0 2px 2px rgb(0 0 0 / .35))">
                <path d="M15 1C7.3 1 1 7.2 1 14.9 1 25.3 15 39 15 39s14-13.7 14-24.1C29 7.2 22.7 1 15 1z" fill="${warna}" stroke="#fff" stroke-width="2"/>
                <circle cx="15" cy="15" r="5" fill="#fff"/></svg>`,
    });
}

function muatCss(href) {
    if (!href || document.querySelector(`link[href="${CSS.escape(href)}"]`)) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('petaWakaf', (opsi = {}) => {
        const mode = opsi.mode ?? 'publik';
        const pakaiPoligon = mode === 'pilih' && 'batas' in opsi;

        // Objek Leaflet disimpan di closure (bukan state reaktif Alpine) agar tidak dibungkus Proxy.
        let peta = null;
        let lapisanBasemap = null;
        let lapisanData = null;
        let kontrolBasemap = null;
        let penanda = null;
        let poligon = null;
        let batasTerakhir = null;
        let pengamatUkuran = null;
        let pembatal = null;
        let nomorPermintaan = 0;
        let dihancurkan = false;
        const pendengar = [];

        const dengar = (nama, fn) => {
            window.addEventListener(nama, fn);
            pendengar.push([nama, fn]);
        };

        return {
            basemap: 'peta',
            memuat: false,
            galat: null,
            jumlah: null,
            ringkasan: null,
            filter: { ...(opsi.filter ?? {}) },
            // Mode pilih (boleh berupa $wire.entangle(...)).
            lat: opsi.lat,
            lng: opsi.lng,
            batas: opsi.batas,

            async init() {
                // Geoman harus dimuat sebelum L.map dibuat agar peta mendapat `peta.pm`.
                if (pakaiPoligon) {
                    muatCss(opsi.cssGeoman);
                    await import('@geoman-io/leaflet-geoman-free');
                    if (dihancurkan) return;
                }

                const wadah = this.$refs.peta ?? this.$el;
                // Snapshot halaman (wire:navigate kembali) bisa menyisakan DOM Leaflet lama.
                wadah.querySelectorAll(':scope > .leaflet-pane, :scope > .leaflet-control-container').forEach((n) => n.remove());

                peta = L.map(wadah, {
                    center: opsi.pusat ?? PUSAT_DEFAULT,
                    zoom: opsi.zoom ?? ZOOM_DEFAULT,
                    scrollWheelZoom: opsi.scrollZoom ?? true,
                    zoomControl: true,
                });

                this.gantiBasemap('peta');
                this.pasangKontrolBasemap();
                if (opsi.legenda ?? mode !== 'pilih') this.pasangLegenda();
                if (opsi.batasWilayah) this.muatBatasWilayah(opsi.batasWilayah);

                lapisanData = L.featureGroup().addTo(peta);

                pengamatUkuran = new ResizeObserver(() => peta?.invalidateSize());
                pengamatUkuran.observe(wadah);

                dengar('peta-pindah', (e) => this.pindah(e.detail ?? {}));

                if (mode === 'publik') {
                    dengar(opsi.event ?? 'peta-filter', (e) => this.muat(e.detail?.filter ?? e.detail ?? {}));
                    this.muat();
                } else if (mode === 'data') {
                    this.tampilkan(opsi.data ?? []);
                } else if (mode === 'pilih') {
                    this.initPilih();
                }
            },

            destroy() {
                dihancurkan = true;
                pembatal?.abort();
                pendengar.forEach(([nama, fn]) => window.removeEventListener(nama, fn));
                pengamatUkuran?.disconnect();
                peta?.off();
                peta?.remove();
                peta = lapisanBasemap = lapisanData = kontrolBasemap = penanda = poligon = null;
            },

            /* ---------- Basemap, legenda, batas wilayah ---------- */

            gantiBasemap(nama) {
                if (!peta || !BASEMAP[nama]) return;

                lapisanBasemap?.remove();
                lapisanBasemap = BASEMAP[nama].buat().addTo(peta);
                lapisanBasemap.bringToBack();
                this.basemap = nama;

                kontrolBasemap?.getContainer()?.querySelectorAll('button[data-basemap]').forEach((tombol) => {
                    const aktif = tombol.dataset.basemap === nama;
                    tombol.setAttribute('aria-pressed', aktif ? 'true' : 'false');
                    tombol.style.background = aktif ? HIJAU_TUA : '#fff';
                    tombol.style.color = aktif ? '#fff' : '#404040';
                });
            },

            pasangKontrolBasemap() {
                const self = this;
                const Kontrol = L.Control.extend({
                    options: { position: 'topright' },
                    onAdd() {
                        const div = L.DomUtil.create('div', 'leaflet-bar');
                        div.setAttribute('role', 'group');
                        div.setAttribute('aria-label', 'Jenis peta');
                        div.style.cssText = 'display:flex;overflow:hidden;background:#fff;border-radius:.5rem';
                        Object.entries(BASEMAP).forEach(([kunci, { label }]) => {
                            const tombol = L.DomUtil.create('button', '', div);
                            tombol.type = 'button';
                            tombol.dataset.basemap = kunci;
                            tombol.textContent = label;
                            tombol.style.cssText = 'border:0;padding:.35rem .8rem;font:600 .75rem/1.2 inherit;cursor:pointer';
                            L.DomEvent.on(tombol, 'click', (e) => {
                                L.DomEvent.preventDefault(e);
                                self.gantiBasemap(kunci);
                            });
                        });
                        L.DomEvent.disableClickPropagation(div);

                        return div;
                    },
                });
                kontrolBasemap = new Kontrol().addTo(peta);
                this.gantiBasemap(this.basemap);
            },

            pasangLegenda() {
                const Legenda = L.Control.extend({
                    options: { position: 'bottomleft' },
                    onAdd() {
                        const div = L.DomUtil.create('div');
                        div.style.cssText = 'background:rgb(255 255 255 / .95);padding:.5rem .75rem;border-radius:.5rem;box-shadow:0 1px 4px rgb(0 0 0 / .2);font-size:.75rem;line-height:1.6;color:#404040';
                        div.innerHTML =
                            '<p style="margin:0 0 .15rem;font-weight:600;color:#123f22">Keterangan</p>' +
                            LEGENDA.map(
                                ([kunci, label]) =>
                                    `<div style="display:flex;align-items:center;gap:.4rem"><span style="display:inline-block;width:.7rem;height:.7rem;border-radius:9999px;border:2px solid #fff;box-shadow:0 0 0 1px ${WARNA[kunci]};background:${WARNA[kunci]}"></span>${label}</div>`,
                            ).join('');
                        L.DomEvent.disableClickPropagation(div);

                        return div;
                    },
                });
                new Legenda().addTo(peta);
            },

            async muatBatasWilayah(url) {
                try {
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!res.ok || !peta) return;
                    L.geoJSON(await res.json(), {
                        interactive: false,
                        pmIgnore: true,
                        style: { color: HIJAU_TUA, weight: 2, dashArray: '6 4', fill: false },
                    }).addTo(peta);
                } catch {
                    // Garis batas hanya pelengkap; abaikan jika gagal dimuat.
                }
            },

            pindah({ lat, lng, zoom }) {
                if (peta && koordinatValid(lat, lng)) peta.flyTo([parseFloat(lat), parseFloat(lng)], zoom ?? Math.max(peta.getZoom(), 13));
            },

            /* ---------- Mode publik & data ---------- */

            async muat(filterBaru = null) {
                if (!opsi.url) return;
                if (filterBaru) this.filter = { ...filterBaru };

                pembatal?.abort();
                pembatal = new AbortController();
                const nomor = ++nomorPermintaan;
                this.memuat = true;
                this.galat = null;

                try {
                    const url = new URL(opsi.url, window.location.origin);
                    Object.entries(this.filter ?? {}).forEach(([k, v]) => {
                        if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
                    });

                    const res = await fetch(url, { headers: { Accept: 'application/json' }, signal: pembatal.signal });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const json = await res.json();
                    if (dihancurkan || nomor !== nomorPermintaan) return;

                    this.ringkasan = json.ringkasan ?? null;
                    this.tampilkan(json.features ?? []);
                } catch (e) {
                    if (e.name === 'AbortError' || dihancurkan) return;
                    this.galat = 'Data peta gagal dimuat. Silakan coba lagi.';
                } finally {
                    if (nomor === nomorPermintaan) this.memuat = false;
                }
            },

            tampilkan(daftar) {
                if (!peta) return;

                lapisanData.clearLayers();
                const items = (Array.isArray(daftar) ? daftar : []).map(normalisasi).filter(Boolean);
                const tombolDetail = opsi.tombolDetail ?? true;

                items.forEach((t) => {
                    const warna = warnaAman(t);
                    const popup = isiPopup(t, tombolDetail);

                    if (t.batas) {
                        try {
                            L.geoJSON(t.batas, {
                                pmIgnore: true,
                                style: { color: warna, weight: 2, fillColor: warna, fillOpacity: 0.18 },
                            })
                                .bindPopup(popup, { maxWidth: 280 })
                                .addTo(lapisanData);
                        } catch {
                            // GeoJSON batas rusak: cukup tampilkan titiknya.
                        }
                    }

                    L.circleMarker([t.lat, t.lng], {
                        pmIgnore: true,
                        radius: 8,
                        color: '#fff',
                        weight: 2,
                        fillColor: warna,
                        fillOpacity: 0.95,
                    })
                        .bindTooltip(esc(t.nama), { direction: 'top', offset: [0, -8] })
                        .bindPopup(popup, { maxWidth: 280 })
                        .addTo(lapisanData);
                });

                this.jumlah = items.length;
                this.paskanTampilan(items);
                this.$dispatch('peta-dimuat', { jumlah: items.length, ringkasan: this.ringkasan });
            },

            paskanTampilan(items) {
                if (!peta) return;

                if (items.length === 0) {
                    peta.setView(opsi.pusat ?? PUSAT_DEFAULT, opsi.zoom ?? ZOOM_DEFAULT);
                } else if (items.length === 1 && !items[0].batas) {
                    peta.setView([items[0].lat, items[0].lng], opsi.zoom ?? ZOOM_TITIK);
                } else {
                    peta.fitBounds(lapisanData.getBounds(), { padding: [30, 30], maxZoom: ZOOM_TITIK });
                }
            },

            /* ---------- Mode pilih ---------- */

            initPilih() {
                this.jumlah = 0;

                if (koordinatValid(this.lat, this.lng)) {
                    this.taruhPenanda(parseFloat(this.lat), parseFloat(this.lng));
                    peta.setView([parseFloat(this.lat), parseFloat(this.lng)], opsi.zoom ?? ZOOM_TITIK);
                }

                peta.on('click', (e) => this.setTitik(e.latlng.lat, e.latlng.lng));
                this.pasangKontrolLokasiSaya();

                const sinkron = () => {
                    if (!koordinatValid(this.lat, this.lng)) {
                        penanda?.remove();
                        penanda = null;
                    } else {
                        this.taruhPenanda(parseFloat(this.lat), parseFloat(this.lng));
                    }
                };
                this.$watch('lat', sinkron);
                this.$watch('lng', sinkron);

                if (!pakaiPoligon || !peta.pm) return;

                peta.pm.setLang('id');
                peta.pm.setGlobalOptions({
                    pathOptions: { color: HIJAU_TUA, weight: 2, fillColor: '#2E8B4E', fillOpacity: 0.2 },
                    snappable: true,
                });
                peta.pm.addControls({
                    position: 'topleft',
                    drawMarker: false,
                    drawCircleMarker: false,
                    drawPolyline: false,
                    drawCircle: false,
                    drawText: false,
                    drawRectangle: true,
                    drawPolygon: true,
                    editMode: true,
                    dragMode: true,
                    cutPolygon: false,
                    removalMode: true,
                    rotateMode: false,
                });

                peta.on('pm:create', ({ layer }) => {
                    if (poligon && poligon !== layer) poligon.remove();
                    this.pakaiPoligon(layer);
                    this.simpanBatas();
                });
                peta.on('pm:remove', ({ layer }) => {
                    if (layer === poligon) {
                        poligon = null;
                        this.simpanBatas();
                    }
                });

                this.gambarBatas(this.batas);
                if (poligon && !penanda) peta.fitBounds(poligon.getBounds(), { padding: [30, 30], maxZoom: 18 });

                this.$watch('batas', (nilai) => {
                    if (JSON.stringify(nilai ?? null) !== batasTerakhir) this.gambarBatas(nilai);
                });
            },

            setTitik(lat, lng) {
                this.lat = bulat7(lat);
                this.lng = bulat7(lng);
                this.taruhPenanda(this.lat, this.lng);
            },

            taruhPenanda(lat, lng) {
                if (!peta) return;

                if (penanda) {
                    penanda.setLatLng([lat, lng]);
                    return;
                }

                penanda = L.marker([lat, lng], {
                    icon: ikonPin(),
                    draggable: true,
                    autoPan: true,
                    pmIgnore: true,
                    keyboard: true,
                    title: 'Lokasi tanah wakaf (geser untuk memindahkan)',
                }).addTo(peta);
                penanda.on('dragend', () => {
                    const { lat: a, lng: b } = penanda.getLatLng();
                    this.setTitik(a, b);
                });
            },

            hapusTitik() {
                this.lat = null;
                this.lng = null;
                penanda?.remove();
                penanda = null;
            },

            pasangKontrolLokasiSaya() {
                if (!('geolocation' in navigator)) return;

                const self = this;
                const Kontrol = L.Control.extend({
                    options: { position: 'topright' },
                    onAdd() {
                        const tombol = L.DomUtil.create('button', 'leaflet-bar');
                        tombol.type = 'button';
                        tombol.textContent = 'Lokasi Saya';
                        tombol.title = 'Gunakan lokasi perangkat saya';
                        tombol.style.cssText = 'background:#fff;border-radius:.5rem;padding:.35rem .8rem;font:600 .75rem/1.2 inherit;color:#1F6B3A;cursor:pointer';
                        L.DomEvent.on(tombol, 'click', (e) => {
                            L.DomEvent.stop(e);
                            navigator.geolocation.getCurrentPosition(
                                ({ coords }) => {
                                    self.setTitik(coords.latitude, coords.longitude);
                                    peta?.setView([coords.latitude, coords.longitude], 17);
                                },
                                () => window.alert('Lokasi perangkat tidak dapat diakses.'),
                                { enableHighAccuracy: true, timeout: 10000 },
                            );
                        });

                        return tombol;
                    },
                });
                new Kontrol().addTo(peta);
            },

            pakaiPoligon(layer) {
                poligon = layer;
                layer.on('pm:edit pm:dragend', () => this.simpanBatas());
            },

            gambarBatas(nilai) {
                if (!peta) return;

                poligon?.remove();
                poligon = null;
                batasTerakhir = JSON.stringify(nilai ?? null);
                if (!nilai) return;

                try {
                    const layer = L.geoJSON(nilai).getLayers()[0];
                    if (!layer) return;
                    layer.setStyle({ color: HIJAU_TUA, weight: 2, fillColor: '#2E8B4E', fillOpacity: 0.2 });
                    layer.addTo(peta);
                    this.pakaiPoligon(layer);
                } catch {
                    // GeoJSON tidak valid: abaikan, pengguna bisa menggambar ulang.
                }
            },

            simpanBatas() {
                const nilai = poligon ? poligon.toGeoJSON().geometry : null;
                batasTerakhir = JSON.stringify(nilai);
                this.batas = nilai;
            },
        };
    });
});
