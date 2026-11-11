import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
Alpine.start();

const chartPalette = {
    teal: '#0891b2',
    tealFill: 'rgba(8, 145, 178, .14)',
    navy: '#172b4d',
    grid: 'rgba(100, 130, 170, .22)',
    muted: '#34496a',
};

function readJson(value) {
    try { return JSON.parse(value || '[]'); } catch { return []; }
}

function commonOptions(unit = '') {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#10213b',
                padding: 12,
                displayColors: false,
                callbacks: { label: context => `${context.parsed.y ?? context.parsed} ${unit}` },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { color: chartPalette.muted, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
            },
            y: {
                beginAtZero: false,
                grid: { color: chartPalette.grid, drawBorder: false },
                border: { display: false, dash: [4, 4] },
                ticks: { color: chartPalette.muted, padding: 8 },
            },
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const readings = document.getElementById('grafico-lecturas');
    if (readings) {
        const labels = readJson(readings.dataset.labels);
        const values = readJson(readings.dataset.valores);
        const unit = readings.dataset.unidad || '';
        const sensorName = readings.dataset.sensor || 'Lecturas';

        new Chart(readings, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: sensorName,
                    data: values,
                    borderColor: chartPalette.teal,
                    backgroundColor: chartPalette.tealFill,
                    borderWidth: 2.5,
                    tension: .25,
                    cubicInterpolationMode: 'monotone',
                    fill: true,
                    pointRadius: values.length > 35 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2,
                }],
            },
            options: (() => {
                const opts = commonOptions(unit);
                opts.scales.y.beginAtZero = true;
                opts.scales.y.grace = '10%';
                return opts;
            })(),
        });
    }

    const pumpStatus = document.getElementById('grafico-estado-bombas');
    if (pumpStatus) {
        const values = readJson(pumpStatus.dataset.valores);
        new Chart(pumpStatus, {
            type: 'doughnut',
            data: {
                labels: ['Funcionando', 'Sin funcionamiento detectado'],
                datasets: [{
                    data: values,
                    backgroundColor: ['#0891b2', '#cfe0f2'],
                    borderWidth: 0,
                    hoverOffset: 5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18, color: chartPalette.muted } },
                    tooltip: { backgroundColor: '#10213b', padding: 12 },
                },
            },
        });
    }

    const consumption = document.getElementById('grafico-consumo');
    if (consumption) {
        const labels = readJson(consumption.dataset.labels);
        const values = readJson(consumption.dataset.valores);
        const unit = consumption.dataset.unidad || '';
        new Chart(consumption, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: unit === 'L' ? 'Agua bombeada' : 'Tiempo funcionando',
                    data: values,
                    backgroundColor: values.map((_, i) => i === values.length - 1 ? '#0891b2' : 'rgba(8,145,178,.4)'),
                    borderRadius: 7,
                    borderSkipped: false,
                    maxBarThickness: 38,
                }],
            },
            options: {
                ...commonOptions(unit),
                scales: {
                    ...commonOptions(unit).scales,
                    y: { ...commonOptions(unit).scales.y, beginAtZero: true },
                },
            },
        });
    }
});