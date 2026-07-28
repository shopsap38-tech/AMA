<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';

$pageTitle = 'Entrepôt 3D';
$refresh   = 0; // pas de rafraîchissement auto par défaut (vue 3D interactive)

$erreur = null;
$data   = null;
$occ    = null;
try {
    $lignes = charger_toutes_lignes();
    $occ    = calculer_occupation($lignes, (float) CAPACITE_PALETTES);

    // Palette de couleurs par catégorie.
    $palette = ['#4C78A8', '#F58518', '#54A24B', '#E45756', '#72B7B2',
                '#EECA3B', '#B279A2', '#FF9DA6', '#9D755D', '#BAB0AC'];

    // Palettes par catégorie.
    $cats = [];
    foreach ($lignes as $l) {
        $dispo = (float) ($l['Disponible'] ?? 0);
        $par   = (float) ($l['U_Qte_Palette'] ?? 0);
        if ($par <= 0 || $dispo <= 0) {
            continue;
        }
        $n   = (int) ceil($dispo / $par);
        $cat = trim((string) ($l['U_u_cat'] ?? '')) ?: '(sans catégorie)';
        $cats[$cat] = ($cats[$cat] ?? 0) + $n;
    }
    arsort($cats);

    $categories = [];
    $idx = 0;
    foreach ($cats as $name => $count) {
        $categories[] = ['name' => $name, 'color' => $palette[$idx % count($palette)], 'count' => $count];
        $idx++;
    }

    // Liste des emplacements occupés (index de catégorie), plafonnée pour la 3D.
    $capRender = 5000;
    $slots = [];
    foreach ($categories as $i => $c) {
        for ($k = 0; $k < $c['count'] && count($slots) < $capRender; $k++) {
            $slots[] = $i;
        }
    }

    $data = [
        'capacity'   => (int) round($occ['capacite']),
        'levels'     => 3,
        'slots'      => $slots,
        'categories' => $categories,
        'kpis'       => [
            'occupied'  => (int) round($occ['occupees']),
            'free'      => (int) round($occ['libres']),
            'capacity'  => (int) round($occ['capacite']),
            'taux_occ'  => round($occ['taux_occupation'], 1),
            'taux_disp' => round($occ['taux_disponible'], 1),
            'rendered'  => count($slots),
            'overflow'  => $occ['depassement'],
        ],
    ];
} catch (Throwable $e) {
    $erreur = $e->getMessage();
}

function fmt($v, int $d = 0): string { return number_format((float) $v, $d, ',', ' '); }
function coul_taux(float $t): string { return $t >= 90 ? '#c92a2a' : ($t >= 70 ? '#e8590c' : '#2b8a3e'); }

$magasin = MAGASIN_FILTRE !== '' ? MAGASIN_FILTRE : 'Tous magasins';

require_once __DIR__ . '/includes/header.php';
?>

<div class="report-head">
    <h2>Entrepôt 3D — <?= htmlspecialchars($magasin) ?></h2>
    <div class="no-print" style="display:flex;gap:0.5rem;align-items:center;">
        <button id="btn-rotate" class="btn3d active" type="button">Rotation auto</button>
        <button id="btn-reset-view" class="btn3d" type="button">Recentrer</button>
        <a class="btn-export" href="entrepot3d.php" style="background:#2563eb;">Actualiser</a>
    </div>
</div>

<?php if ($erreur): ?>
    <p class="alert alert-error"><strong>Impossible de charger les données :</strong><br>
        <?= nl2br(htmlspecialchars($erreur)) ?></p>
    <p class="hint">Vérifiez la connexion (voir <code>test.php</code>) et la source configurée.</p>
<?php else: ?>

    <?php $c = coul_taux($occ['taux_occupation']); ?>

    <!-- KPI -->
    <div class="kpis" style="margin-bottom:1rem;">
        <div class="kpi"><span class="kpi-label">Capacité</span>
            <span class="kpi-value"><?= fmt($occ['capacite']) ?></span><span class="kpi-unit">emplacements</span></div>
        <div class="kpi"><span class="kpi-label">Palettes occupées</span>
            <span class="kpi-value" style="color:<?= $c ?>;"><?= fmt($occ['occupees']) ?></span><span class="kpi-unit">palettes</span></div>
        <div class="kpi"><span class="kpi-label">Palettes libres</span>
            <span class="kpi-value" style="color:#2b8a3e;"><?= fmt($occ['libres']) ?></span><span class="kpi-unit">palettes</span></div>
        <div class="kpi"><span class="kpi-label">Taux d'occupation</span>
            <span class="kpi-value" style="color:<?= $c ?>;"><?= fmt($occ['taux_occupation'], 1) ?>%</span><span class="kpi-unit">de la capacité</span></div>
        <div class="kpi"><span class="kpi-label">Espace disponible</span>
            <span class="kpi-value" style="color:#2b8a3e;"><?= fmt($occ['taux_disponible'], 1) ?>%</span><span class="kpi-unit">de la capacité</span></div>
    </div>

    <?php if ($data['kpis']['overflow']): ?>
        <p class="alert alert-error">⚠ Palettes occupées (<?= fmt($occ['occupees']) ?>)
            supérieures à la capacité (<?= fmt($occ['capacite']) ?>). Ajustez
            <code>CAPACITE_PALETTES</code> dans <code>config/database.php</code>.</p>
    <?php endif; ?>

    <!-- Filtres catégorie -->
    <div class="chips no-print">
        <button class="chip active" data-cat="-1" type="button">Toutes</button>
        <?php foreach ($data['categories'] as $i => $cat): ?>
            <button class="chip" data-cat="<?= (int) $i ?>" type="button">
                <span class="chip-dot" style="background:<?= htmlspecialchars($cat['color']) ?>;"></span>
                <?= htmlspecialchars($cat['name']) ?> (<?= fmt($cat['count']) ?>)
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Scène 3D -->
    <div id="scene-wrap">
        <div id="scene"></div>
        <div id="scene-tip"></div>
        <div class="scene-help no-print">Glisser = pivoter · molette = zoom · clic droit = déplacer</div>
    </div>

    <?php if ($data['kpis']['rendered'] < $occ['occupees']): ?>
        <p class="hint">Affichage limité à <?= fmt($data['kpis']['rendered']) ?> palettes sur
            <?= fmt($occ['occupees']) ?> (performance 3D). Les indicateurs restent exacts.</p>
    <?php endif; ?>

    <script>window.WAREHOUSE_DATA = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/rapport-stock/assets/vendor/three.min.js"></script>
    <script src="/rapport-stock/assets/vendor/OrbitControls.js"></script>
    <script src="/rapport-stock/assets/entrepot3d.js"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
