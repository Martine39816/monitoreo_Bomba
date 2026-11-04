import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('grafico-lecturas');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const valores = JSON.parse(canvas.dataset.valores || '[]');
    const unidad = canvas.dataset.unidad || '';
    const nombreSensor = canvas.dataset.sensor || '';

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: nombreSensor + (unidad ? ' (' + unidad + ')' : ''),
                data: valores,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.15)',
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                pointHoverRadius: 5,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
            },
            scales: {
                y: { beginAtZero: false },
            },
        },
    });
});
