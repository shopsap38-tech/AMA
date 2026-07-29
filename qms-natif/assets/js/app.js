/* =============================================================================
   QMS — Script applicatif (ES6)
   Thème, navigation, notifications, graphiques Chart.js, upload drag & drop,
   DataTables.
   ========================================================================== */
(function () {
    'use strict';

    const QMS = window.QMS || {};
    window.QMS = QMS;

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const base = () => document.querySelector('base')?.href || '';

    /* ----------------------------------------------------------------- Thème */
    const THEME_KEY = 'qms-theme';
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        const icon = document.querySelector('#themeToggle i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
        // Rafraîchit les graphiques pour adapter les couleurs.
        if (QMS._chartData) { QMS.renderCharts(QMS._chartData); }
    }
    function initTheme() {
        const saved = localStorage.getItem(THEME_KEY) || 'light';
        applyTheme(saved);
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        });
    }

    /* ------------------------------------------------------------ Sidebar */
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggle = document.getElementById('sidebarToggle');
        const close = () => { sidebar?.classList.remove('open'); backdrop?.classList.remove('show'); };
        toggle?.addEventListener('click', () => { sidebar?.classList.toggle('open'); backdrop?.classList.toggle('show'); });
        backdrop?.addEventListener('click', close);
    }

    /* -------------------------------------------------------- Recherche */
    function initGlobalSearch() {
        const input = document.getElementById('globalSearch');
        if (!input) return;
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && input.value.trim() !== '') {
                window.location = 'nonconformites.php?keyword=' + encodeURIComponent(input.value.trim());
            }
        });
    }

    /* --------------------------------------------------- Notifications */
    function timeAgo(iso) {
        const diff = Math.floor((Date.now() - new Date(iso.replace(' ', 'T')).getTime()) / 1000);
        if (diff < 60) return "à l'instant";
        if (diff < 3600) return Math.floor(diff / 60) + ' min';
        if (diff < 86400) return Math.floor(diff / 3600) + ' h';
        return Math.floor(diff / 86400) + ' j';
    }
    function renderNotifications(data) {
        const dot = document.getElementById('notifDot');
        const list = document.getElementById('notifList');
        if (dot) dot.classList.toggle('d-none', (data.unread || 0) === 0);
        if (!list) return;
        if (!data.items || data.items.length === 0) {
            list.innerHTML = '<div class="notif-empty">Aucune notification</div>';
            return;
        }
        list.innerHTML = data.items.map((n) => `
            <div class="notif-item ${Number(n.is_read) === 0 ? 'unread' : ''}">
                <div class="notif-ico"><i class="fa-solid fa-bell"></i></div>
                <div class="notif-body">
                    <strong>${escapeHtml(n.title)}</strong>
                    <span>${escapeHtml(n.message)}</span>
                    <time>${timeAgo(n.created_at)}</time>
                </div>
            </div>`).join('');
    }
    function pollNotifications() {
        fetch('api_notifications.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.ok ? r.json() : null)
            .then((d) => d && renderNotifications(d))
            .catch(() => {});
    }
    function initNotifications() {
        if (!document.getElementById('notifList')) return;
        pollNotifications();
        setInterval(pollNotifications, 30000);
    }

    /* ------------------------------------------------------- Utilitaires */
    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }
    function cssVar(name) { return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); }
    function palette() {
        return {
            brand: cssVar('--brand') || '#0a6ed1',
            success: cssVar('--success') || '#107e3e',
            warning: cssVar('--warning') || '#e9730c',
            danger: cssVar('--danger') || '#bb0000',
            grid: cssVar('--border') || '#e3e8ee',
            text: cssVar('--text-soft') || '#5b6b7c',
            purple: '#8e44ad', teal: '#17a2b8', slate: '#6b7b8c'
        };
    }

    /* ---------------------------------------------------- Graphiques */
    QMS.renderCharts = function (data) {
        QMS._chartData = data;
        const p = palette();
        Chart.defaults.font.family = 'Segoe UI, sans-serif';
        Chart.defaults.color = p.text;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;

        QMS._charts = QMS._charts || {};
        const destroy = (k) => { if (QMS._charts[k]) { QMS._charts[k].destroy(); } };
        const make = (id, cfg) => {
            const el = document.getElementById(id);
            if (!el) return;
            destroy(id);
            QMS._charts[id] = new Chart(el, cfg);
        };

        const gridCfg = { grid: { color: p.grid, drawBorder: false }, ticks: { color: p.text } };

        // Line — évolution mensuelle
        if (data.trend) {
            make('chartTrend', {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [
                        { label: 'Déclarées', data: data.trend.total, borderColor: p.brand, backgroundColor: hexA(p.brand, 0.12), fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 3 },
                        { label: 'Clôturées', data: data.trend.closed, borderColor: p.success, backgroundColor: hexA(p.success, 0.08), fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { x: gridCfg, y: { ...gridCfg, beginAtZero: true, ticks: { ...gridCfg.ticks, precision: 0 } } } }
            });
        }

        // Doughnut — statut
        if (data.status) {
            make('chartStatus', {
                type: 'doughnut',
                data: { labels: data.status.labels, datasets: [{ data: data.status.values, backgroundColor: [p.brand, p.purple, p.warning, p.teal, p.success], borderWidth: 2, borderColor: cssVar('--surface') }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } }
            });
        }

        // Pie — gravité
        if (data.severity) {
            make('chartSeverity', {
                type: 'pie',
                data: { labels: data.severity.labels, datasets: [{ data: data.severity.values, backgroundColor: [p.danger, p.warning, p.slate], borderWidth: 2, borderColor: cssVar('--surface') }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }

        // Bar — origine
        if (data.origin) {
            make('chartOrigin', {
                type: 'bar',
                data: { labels: data.origin.labels, datasets: [{ label: 'NC', data: data.origin.values, backgroundColor: hexA(p.brand, 0.85), borderRadius: 6, maxBarThickness: 34 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: gridCfg, y: { ...gridCfg, beginAtZero: true, ticks: { ...gridCfg.ticks, precision: 0 } } } }
            });
        }

        // Bar groupé — performance par service
        if (data.department) {
            make('chartDepartment', {
                type: 'bar',
                data: {
                    labels: data.department.labels,
                    datasets: [
                        { label: 'Total', data: data.department.total, backgroundColor: hexA(p.brand, 0.85), borderRadius: 5, maxBarThickness: 22 },
                        { label: 'Clôturées', data: data.department.closed, backgroundColor: hexA(p.success, 0.85), borderRadius: 5, maxBarThickness: 22 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { x: gridCfg, y: { ...gridCfg, beginAtZero: true, ticks: { ...gridCfg.ticks, precision: 0 } } } }
            });
        }

        // Heatmap (rendu HTML/CSS)
        if (data.heatmap) { renderHeatmap(data.heatmap); }
    };

    function renderHeatmap(hm) {
        const el = document.getElementById('heatmap');
        if (!el) return;
        const max = Math.max(1, ...hm.matrix.flat());
        let html = `<div class="heatmap-row"><div></div>${hm.severities.map((s) => `<div class="heatmap-head">${escapeHtml(s)}</div>`).join('')}</div>`;
        hm.departments.forEach((dept, i) => {
            html += `<div class="heatmap-row"><div class="heatmap-label">${escapeHtml(dept)}</div>`;
            hm.matrix[i].forEach((v) => {
                const ratio = v / max;
                const bg = heatColor(ratio);
                html += `<div class="heatmap-cell" style="background:${bg};color:${ratio > 0.45 ? '#fff' : 'var(--text-soft)'}">${v}</div>`;
            });
            html += '</div>';
        });
        el.innerHTML = html;
    }
    function heatColor(r) {
        if (r === 0) return 'var(--surface-2)';
        // Interpolation bleu clair -> rouge
        const c1 = [10, 110, 209], c2 = [187, 0, 0];
        const mix = c1.map((v, i) => Math.round(v + (c2[i] - v) * r));
        return `rgb(${mix[0]},${mix[1]},${mix[2]})`;
    }
    function hexA(hex, a) {
        const h = hex.replace('#', '');
        const n = h.length === 3 ? h.split('').map((c) => c + c).join('') : h;
        const r = parseInt(n.substr(0, 2), 16), g = parseInt(n.substr(2, 2), 16), b = parseInt(n.substr(4, 2), 16);
        return `rgba(${r},${g},${b},${a})`;
    }

    QMS.initDashboard = function () {
        const node = document.getElementById('dashboard-data');
        if (!node) return;
        try {
            const data = JSON.parse(node.textContent);
            QMS.renderCharts(data);
        } catch (e) { console.error('Dashboard data invalide', e); }

        document.getElementById('refreshDashboard')?.addEventListener('click', function () {
            const btn = this;
            btn.disabled = true;
            fetch('api_dashboard.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => r.json())
                .then((d) => { if (d.charts) QMS.renderCharts(d.charts); })
                .finally(() => { btn.disabled = false; });
        });
    };

    /* --------------------------------------------------- Drag & drop upload */
    function initDropzone() {
        const zone = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');
        const preview = document.getElementById('filePreview');
        if (!zone || !input) return;

        zone.addEventListener('click', () => input.click());
        ['dragover', 'dragenter'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('dragover'); }));
        ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.remove('dragover'); }));
        zone.addEventListener('drop', (e) => { input.files = e.dataTransfer.files; showPreview(); });
        input.addEventListener('change', showPreview);

        function showPreview() {
            if (!preview) return;
            preview.innerHTML = Array.from(input.files).map((f) =>
                `<div class="file-chip"><i class="fa-solid fa-paperclip"></i>${escapeHtml(f.name)} <small>(${(f.size / 1024).toFixed(0)} Ko)</small></div>`
            ).join('');
        }
    }

    /* ------------------------------------------------------- DataTables */
    function initDataTables() {
        if (!window.jQuery || !jQuery.fn.DataTable) return;
        jQuery('.js-datatable').each(function () {
            jQuery(this).DataTable({
                pageLength: 15,
                lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, 'Tous']],
                order: [],
                language: {
                    search: 'Rechercher :', lengthMenu: 'Afficher _MENU_ entrées',
                    info: '_START_ à _END_ sur _TOTAL_ entrées', infoEmpty: 'Aucune entrée',
                    infoFiltered: '(filtré sur _MAX_ entrées)', zeroRecords: 'Aucun résultat',
                    paginate: { first: '«', last: '»', next: '›', previous: '‹' }
                }
            });
        });
    }

    /* -------------------------------------------------- Confirmation delete */
    function initConfirm() {
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                if (!window.confirm(form.getAttribute('data-confirm'))) { e.preventDefault(); }
            });
        });
    }

    /* --------------------------------------------------------------- Init */
    document.addEventListener('DOMContentLoaded', function () {
        initTheme();
        initSidebar();
        initGlobalSearch();
        initNotifications();
        initDropzone();
        initDataTables();
        initConfirm();
    });
})();
