<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';
require_once __DIR__ . '/includes/warehouse.php';

$pageTitle = 'Entrepôt 3D - WMS';

$erreur = null;
$data   = null;
try {
    $lignes = charger_toutes_lignes();
    $occ    = calculer_occupation($lignes, (float) CAPACITE_PALETTES);
    $data   = construire_entrepot((float) $occ['occupees']);
    $data['magasins'] = magasins_autorises();
} catch (Throwable $e) {
    $erreur = $e->getMessage();
}

function fmt($v, int $d = 0): string { return number_format((float) $v, $d, ',', ' '); }
function coul_zone(float $taux): string
{
    if ($taux >= 80) { return '#c92a2a'; }
    if ($taux >= 50) { return '#e8720c'; }
    return '#2f9e44';
}

$magAutorises = magasins_autorises();
$magasin = $magAutorises ? implode(', ', $magAutorises) : 'Tous magasins';

require_once __DIR__ . '/includes/header.php';
?>

<div class="report-head">
    <h2>Entrepôt 3D — <?= htmlspecialchars($magasin) ?></h2>
    <div class="no-print wms-toolbar">
        <button id="btn-rotate" class="btn3d active" type="button">Rotation auto</button>
        <button id="btn-codes" class="btn3d active" type="button">Codes racks</button>
        <button id="btn-reset-view" class="btn3d" type="button">Recentrer</button>
        <a class="btn-export" href="entrepot3d.php" style="background:#2563eb;">Actualiser</a>
    </div>
</div>

<?php if ($erreur): ?>
    <p class="alert alert-error"><strong>Impossible de charger les données :</strong><br>
        <?= nl2br(htmlspecialchars($erreur)) ?></p>
    <p class="hint">Vérifiez la connexion (voir <code>test.php</code>) et la source configurée.</p>
<?php else: ?>

    <!-- KPI globaux -->
    <div class="kpis" style="margin-bottom:1rem;">
        <div class="kpi"><span class="kpi-label">Capacité totale</span>
            <span class="kpi-value" id="kpi-cap"><?= fmt($data['capacite_totale']) ?></span><span class="kpi-unit"><?= (int) $data['nb_racks'] ?> racks · 8 zones</span></div>
        <div class="kpi"><span class="kpi-label">Palettes occupées</span>
            <span class="kpi-value" id="kpi-occ" style="color:<?= coul_zone($data['taux']) ?>;"><?= fmt($data['occupees']) ?></span><span class="kpi-unit">palettes</span></div>
        <div class="kpi"><span class="kpi-label">Palettes disponibles</span>
            <span class="kpi-value" id="kpi-libre" style="color:#2f9e44;"><?= fmt($data['libres']) ?></span><span class="kpi-unit">emplacements libres</span></div>
        <div class="kpi"><span class="kpi-label">Taux d'occupation</span>
            <span class="kpi-value" id="kpi-taux" style="color:<?= coul_zone($data['taux']) ?>;"><?= fmt($data['taux'], 1) ?>%</span><span class="kpi-unit">global</span></div>
    </div>

    <!-- Taux par zone -->
    <div class="zone-bars" id="zone-bars">
        <?php foreach ($data['zones'] as $z): ?>
            <div class="zone-bar" data-zone="<?= htmlspecialchars($z['zone']) ?>" title="Zone <?= htmlspecialchars($z['zone']) ?> — <?= fmt($z['occupied']) ?>/<?= fmt($z['capacity']) ?>">
                <div class="zone-bar-head"><strong>Zone <?= htmlspecialchars($z['zone']) ?></strong><span><?= fmt($z['rate'], 1) ?>%</span></div>
                <div class="zone-bar-track"><div class="zone-bar-fill" style="width:<?= min($z['rate'], 100) ?>%;background:<?= coul_zone($z['rate']) ?>;"></div></div>
                <div class="zone-bar-sub"><?= fmt($z['occupied']) ?> / <?= fmt($z['capacity']) ?> · <?= (int) $z['racks'] ?> racks</div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Légende -->
    <div class="wms-legend no-print">
        <span><span class="dot" style="background:#2f9e44;"></span>Faible (&lt; 50 %)</span>
        <span><span class="dot" style="background:#e8720c;"></span>Moyenne (50–80 %)</span>
        <span><span class="dot" style="background:#c92a2a;"></span>Presque plein (&gt; 80 %)</span>
    </div>

    <!-- Scène 3D + panneau d'info -->
    <div class="wms-stage">
        <div id="scene-wrap">
            <div id="scene"></div>
            <div id="scene-tip"></div>
            <div class="scene-help no-print">Glisser = pivoter · molette = zoom · clic droit = déplacer · clic sur un rack = détail</div>
        </div>
        <aside class="rack-panel" id="rack-panel">
            <div class="rack-panel-empty" id="rack-empty">
                <div class="rack-panel-icon">🏗️</div>
                Cliquez sur un rack pour afficher son détail.
            </div>
            <div class="rack-panel-body" id="rack-body" style="display:none;">
                <div class="rack-panel-code" id="rp-code">A01</div>
                <div class="rack-panel-zone" id="rp-zone">Zone A</div>
                <div class="rack-panel-gauge">
                    <div class="rack-panel-gauge-fill" id="rp-gauge"></div>
                </div>
                <div class="rack-panel-rate" id="rp-rate">0 %</div>
                <dl class="rack-panel-list">
                    <div><dt>Capacité</dt><dd id="rp-cap">—</dd></div>
                    <div><dt>Palettes occupées</dt><dd id="rp-occ">—</dd></div>
                    <div><dt>Places libres</dt><dd id="rp-free">—</dd></div>
                </dl>
            </div>
        </aside>
    </div>

    <?php
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) { $flags |= JSON_INVALID_UTF8_SUBSTITUTE; }
    $json = json_encode($data, $flags);
    if ($json === false) { $json = '{"error":' . json_encode(json_last_error_msg()) . '}'; }
    ?>
    <script>window.WAREHOUSE_FALLBACK = <?= $json ?>;</script>
    <script src="assets/vendor/three.min.js"></script>
    <script src="assets/vendor/OrbitControls.js"></script>
    <script src="assets/entrepot3d.js"></script>
    <script>
    (function () {
        var el = document.getElementById('scene');
        if (el && typeof THREE === 'undefined') {
            el.innerHTML = '<div class="scene-error">⚠ <strong>Three.js n\'a pas pu être chargé.</strong><br>'
                + 'Vérifiez <code>assets/vendor/three.min.js</code> (onglet Réseau, statut 200).</div>';
        }
    })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
