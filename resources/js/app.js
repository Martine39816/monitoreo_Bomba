import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.start();

// Prevención global de doble envío en formularios.
// Deshabilita los botones submit inmediatamente después de enviarse el formulario,
// evitando que el usuario haga clic varias veces y genere registros duplicados.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (! (form instanceof HTMLFormElement)) {
        return;
    }

    // Si el formulario tiene una confirmación nativa que fue cancelada, no hacemos nada.
    const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    buttons.forEach((btn) => {
        btn.disabled = true;
        btn.dataset.originalText = btn.dataset.originalText || btn.innerHTML;
        if (btn.tagName === 'BUTTON') {
            btn.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Procesando...</span>';
        }
    });

    // Reactivar los botones después de un tiempo de seguridad por si el servidor no responde.
    setTimeout(() => {
        buttons.forEach((btn) => {
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        });
    }, 10000);
}, true);

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
