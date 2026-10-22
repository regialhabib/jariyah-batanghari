import Chart from 'chart.js/auto';

const HIJAU = '#2E8B4E';
const ORANYE = '#F59E0B';
const BIRU = '#3B82F6';

Chart.defaults.font.family = "'Poppins', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#525252';

/**
 * Grafik batang tumpuk sebaran tanah wakaf per kecamatan.
 *
 * Pemakaian:
 *   <div x-data="grafikSebaran(@js($data))"><canvas x-ref="canvas"></canvas></div>
 * dengan $data = StatistikWakaf::perKecamatan() (array of {kecamatan, sudah, belum, proses}).
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('grafikSebaran', (data) => ({
        chart: null,

        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels: data.map((d) => d.kecamatan),
                    datasets: [
                        { label: 'Sudah Bersertifikat', data: data.map((d) => d.sudah), backgroundColor: HIJAU, borderRadius: 4 },
                        { label: 'Dalam Proses', data: data.map((d) => d.proses), backgroundColor: BIRU, borderRadius: 4 },
                        { label: 'Belum Bersertifikat', data: data.map((d) => d.belum), backgroundColor: ORANYE, borderRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } } },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
                    },
                },
            });
        },

        destroy() {
            this.chart?.destroy();
        },
    }));
});
