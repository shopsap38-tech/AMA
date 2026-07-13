/**
 * Plugin Chart.js : affiche le pourcentage au-dessus (ou à droite) de chaque
 * colonne d'un histogramme. Le pourcentage est calculé par rapport à la somme
 * des valeurs du même jeu de données (dataset).
 *
 * Nécessite Chart.js chargé au préalable. S'enregistre globalement et ne
 * s'applique qu'aux graphiques de type "bar".
 */
(function () {
    if (typeof Chart === 'undefined') { return; }

    // Polices plus grandes pour une meilleure lisibilité.
    Chart.defaults.font.size = 14;
    Chart.defaults.color = '#334155';

    // De l'air au-dessus des barres pour ne pas couper les étiquettes.
    Chart.defaults.layout = Chart.defaults.layout || {};
    Chart.defaults.layout.padding = { top: 28, right: 48 };

    const PercentLabels = {
        id: 'percentLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            const horizontal = chart.options.indexAxis === 'y';
            ctx.save();
            ctx.font = '700 14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillStyle = '#0f172a';

            chart.data.datasets.forEach((ds, di) => {
                const meta = chart.getDatasetMeta(di);
                if (meta.hidden || meta.type !== 'bar') { return; }

                const somme = ds.data.reduce((a, b) => a + (Number(b) || 0), 0);
                if (somme <= 0) { return; }

                meta.data.forEach((bar, i) => {
                    const val = Number(ds.data[i]) || 0;
                    if (val === 0) { return; } // pas d'étiquette sur les colonnes vides
                    const p = Math.round((val / somme) * 1000) / 10; // 1 décimale
                    const label = p + ' %';

                    if (horizontal) {
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(label, bar.x + 8, bar.y);
                    } else {
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(label, bar.x, bar.y - 6);
                    }
                });
            });
            ctx.restore();
        }
    };

    Chart.register(PercentLabels);

    /**
     * Raccourci pour créer un histogramme (colonnes) avec % au-dessus des barres.
     * @param {HTMLCanvasElement|string} cible  canvas ou son id
     * @param {string[]} labels
     * @param {number[]} data
     * @param {string|string[]} couleurs
     * @param {object} extra  options : { titre, horizontal, max, aspectRatio }
     */
    window.histogramme = function (cible, labels, data, couleurs, extra) {
        extra = extra || {};
        const scaleVal = {
            beginAtZero: true,
            grace: '8%',
            ticks: { precision: 0, font: { size: 13 } },
            grid: { color: 'rgba(148,163,184,.2)' }
        };
        const scaleCat = { ticks: { font: { size: 13 } }, grid: { display: false } };
        if (extra.max) { scaleVal.max = extra.max; }

        return new Chart(cible, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: extra.titre || 'Valeur',
                    data: data,
                    backgroundColor: couleurs,
                    borderRadius: 6,
                    maxBarThickness: 84
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: extra.aspectRatio || (extra.horizontal ? 1.6 : 1.9),
                indexAxis: extra.horizontal ? 'y' : 'x',
                plugins: { legend: { display: false }, tooltip: { titleFont: { size: 14 }, bodyFont: { size: 14 } } },
                scales: extra.horizontal
                    ? { x: scaleVal, y: scaleCat }
                    : { y: scaleVal, x: scaleCat }
            }
        });
    };
})();
