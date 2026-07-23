// Rendu des graphiques du tableau de bord (Chart.js)
(function () {
    'use strict';
    var d = window.DE_DASH || {};

    // --- Graphique en colonnes : demandes MTD (≤ seuil vs > seuil) ---
    var barEl = document.getElementById('chartBar');
    if (barEl && window.Chart) {
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: ['≤ ' + d.seuil + ' min', '> ' + d.seuil + ' min'],
                datasets: [{
                    label: 'Demandes',
                    data: [d.dansDelai || 0, d.horsDelai || 0],
                    backgroundColor: ['#198754', '#dc3545'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // --- Graphique linéaire : tendance quotidienne ---
    var lineEl = document.getElementById('chartLine');
    if (lineEl && window.Chart) {
        new Chart(lineEl, {
            type: 'line',
            data: {
                labels: d.trendLabels || [],
                datasets: [
                    {
                        label: '≤ ' + d.seuil + ' min',
                        data: d.trendDans || [],
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,.15)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '> ' + d.seuil + ' min',
                        data: d.trendHors || [],
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220,53,69,.15)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
})();
