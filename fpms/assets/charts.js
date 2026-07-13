/**
 * Plugin Chart.js : affiche le nombre (valeur) au-dessus (ou à droite) de chaque
 * colonne d'un histogramme.
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

    const ValueLabels = {
        id: 'valueLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            const horizontal = chart.options.indexAxis === 'y';
            const stacked = !!((chart.options.scales && chart.options.scales.y && chart.options.scales.y.stacked)
                || (chart.options.scales && chart.options.scales.x && chart.options.scales.x.stacked));
            ctx.save();
            ctx.font = '700 14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

            chart.data.datasets.forEach((ds, di) => {
                const meta = chart.getDatasetMeta(di);
                if (meta.hidden || meta.type !== 'bar') { return; }

                meta.data.forEach((bar, i) => {
                    const val = Number(ds.data[i]) || 0;
                    if (val === 0) { return; } // pas d'étiquette sur les colonnes vides
                    const label = Number.isInteger(val) ? String(val) : String(Math.round(val * 10) / 10);

                    if (stacked) {
                        // Étiquette centrée dans le segment (texte blanc), sauf segment trop fin.
                        const epaisseur = Math.abs(bar.base - bar.y);
                        if (epaisseur < 16) { return; }
                        ctx.fillStyle = '#ffffff';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(label, bar.x, (bar.y + bar.base) / 2);
                    } else if (horizontal) {
                        ctx.fillStyle = '#0f172a';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(label, bar.x + 8, bar.y);
                    } else {
                        ctx.fillStyle = '#0f172a';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(label, bar.x, bar.y - 6);
                    }
                });
            });
            ctx.restore();
        }
    };

    Chart.register(ValueLabels);

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

    /**
     * Histogramme empilé (colonnes empilées) avec la valeur au centre de chaque segment.
     * @param {HTMLCanvasElement|string} cible
     * @param {string[]} labels  catégories de l'axe X (périodes)
     * @param {{label:string,data:number[],color:string}[]} series  séries empilées
     * @param {object} extra  { aspectRatio }
     */
    window.histogrammeEmpile = function (cible, labels, series, extra) {
        extra = extra || {};
        return new Chart(cible, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: series.map(function (s) {
                    return {
                        label: s.label,
                        data: s.data,
                        backgroundColor: s.color,
                        borderRadius: 4,
                        maxBarThickness: 84
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: extra.aspectRatio || 2.2,
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { font: { size: 13 } } },
                    tooltip: { titleFont: { size: 14 }, bodyFont: { size: 14 } }
                },
                scales: {
                    x: { stacked: true, ticks: { font: { size: 13 } }, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, grace: '8%', ticks: { precision: 0, font: { size: 13 } }, grid: { color: 'rgba(148,163,184,.2)' } }
                }
            }
        });
    };
})();
