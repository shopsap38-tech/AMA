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

    // De l'air au-dessus des barres pour ne pas couper les étiquettes.
    Chart.defaults.layout = Chart.defaults.layout || {};
    Chart.defaults.layout.padding = { top: 24, right: 40 };

    const PercentLabels = {
        id: 'percentLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            const horizontal = chart.options.indexAxis === 'y';
            ctx.save();
            ctx.font = '600 12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillStyle = '#1f2933';

            chart.data.datasets.forEach((ds, di) => {
                const meta = chart.getDatasetMeta(di);
                if (meta.hidden || meta.type !== 'bar') { return; }

                const somme = ds.data.reduce((a, b) => a + (Number(b) || 0), 0);
                if (somme <= 0) { return; }

                meta.data.forEach((bar, i) => {
                    const val = Number(ds.data[i]) || 0;
                    const p = Math.round((val / somme) * 1000) / 10; // 1 décimale
                    const label = p + ' %';

                    if (horizontal) {
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(label, bar.x + 6, bar.y);
                    } else {
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(label, bar.x, bar.y - 5);
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
     * @param {object} extra  options : { titre, horizontal, max }
     */
    window.histogramme = function (cible, labels, data, couleurs, extra) {
        extra = extra || {};
        const scaleVal = { beginAtZero: true, ticks: { precision: 0 } };
        if (extra.max) { scaleVal.max = extra.max; }
        return new Chart(cible, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: extra.titre || 'Valeur',
                    data: data,
                    backgroundColor: couleurs,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: extra.horizontal ? 'y' : 'x',
                plugins: { legend: { display: false } },
                scales: extra.horizontal
                    ? { x: scaleVal }
                    : { y: scaleVal }
            }
        });
    };
})();
