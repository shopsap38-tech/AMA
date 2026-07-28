<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';

$pageTitle = 'Occupation du magasin';

// Rafraîchissement auto (secondes) — 0 pour désactiver.
$refresh = 60;

$erreur = null;
$occ    = null;
try {
    $lignes = charger_toutes_lignes();
    $occ    = calculer_occupation($lignes, (float) CAPACITE_PALETTES);
} catch (Throwable $e) {
    $erreur = $e->getMessage();
}

function fmt($v, int $dec = 0): string
{
    return number_format((float) $v, $dec, ',', ' ');
}

/** Couleur sémantique selon le taux d'occupation. */
function couleur_taux(float $taux): string
{
    if ($taux >= 90) { return '#c92a2a'; } // rouge : saturé
    if ($taux >= 70) { return '#e8590c'; } // orange : élevé
    return '#2b8a3e';                      // vert : confortable
}

$magasin = MAGASIN_FILTRE !== '' ? MAGASIN_FILTRE : 'Tous magasins';

require_once __DIR__ . '/includes/header.php';
?>
<?php if ($refresh > 0 && !$erreur): ?>
    <script>setTimeout(function () { location.reload(); }, <?= (int) $refresh * 1000 ?>);</script>
<?php endif; ?>

<div class="report-head">
    <h2>Occupation — <?= htmlspecialchars($magasin) ?></h2>
    <div class="no-print" style="display:flex;gap:1rem;align-items:center;">
        <span class="maj">Actualisé à <?= date('H:i:s') ?><?= $refresh > 0 ? ' · auto ' . $refresh . 's' : '' ?></span>
        <a class="btn-export" href="occupation.php" style="background:#2563eb;">Actualiser</a>
    </div>
</div>

<?php if ($erreur): ?>
    <p class="alert alert-error"><strong>Impossible de charger les données :</strong><br>
        <?= nl2br(htmlspecialchars($erreur)) ?></p>
    <p class="hint">Vérifiez la connexion (voir <code>test.php</code>) et la source configurée.</p>
<?php else: ?>

    <?php if (CAPACITE_PALETTES <= 0): ?>
        <p class="alert alert-error">La capacité du magasin n'est pas définie.
            Renseignez <code>CAPACITE_PALETTES</code> dans <code>config/database.php</code>.</p>
    <?php endif; ?>

    <?php $couleur = couleur_taux($occ['taux_occupation']); ?>
    <?php $angle = min($occ['taux_occupation'], 100) * 3.6; ?>

    <div class="dash">
        <!-- Jauge circulaire -->
        <div class="gauge-card">
            <div class="gauge" style="background:conic-gradient(<?= $couleur ?> <?= $angle ?>deg, #e9ecef 0deg);">
                <div class="gauge-hole">
                    <span class="gauge-pct" style="color:<?= $couleur ?>;"><?= fmt($occ['taux_occupation'], 1) ?>%</span>
                    <span class="gauge-sub">occupé</span>
                </div>
            </div>
            <?php if ($occ['depassement']): ?>
                <p class="depassement">⚠ Capacité dépassée de <?= fmt($occ['occupees'] - $occ['capacite']) ?> palette(s)</p>
            <?php endif; ?>
        </div>

        <!-- Indicateurs clés -->
        <div class="kpis">
            <div class="kpi">
                <span class="kpi-label">Capacité totale</span>
                <span class="kpi-value"><?= fmt($occ['capacite']) ?></span>
                <span class="kpi-unit">emplacements</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Palettes occupées</span>
                <span class="kpi-value" style="color:<?= $couleur ?>;"><?= fmt($occ['occupees'], 2) ?></span>
                <span class="kpi-unit">palettes (fraction)</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Palettes libres</span>
                <span class="kpi-value" style="color:#2b8a3e;"><?= fmt($occ['libres'], 2) ?></span>
                <span class="kpi-unit">palettes</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Taux d'occupation</span>
                <span class="kpi-value" style="color:<?= $couleur ?>;"><?= fmt($occ['taux_occupation'], 1) ?>%</span>
                <span class="kpi-unit">de la capacité</span>
            </div>
            <div class="kpi">
                <span class="kpi-label">Espace disponible</span>
                <span class="kpi-value" style="color:#2b8a3e;"><?= fmt($occ['taux_disponible'], 1) ?>%</span>
                <span class="kpi-unit">de la capacité</span>
            </div>
        </div>
    </div>

    <!-- Barre d'occupation -->
    <div class="occ-bar-wrap">
        <div class="occ-bar">
            <div class="occ-bar-fill" style="width:<?= min($occ['taux_occupation'], 100) ?>%;background:<?= $couleur ?>;">
                <?php if ($occ['taux_occupation'] >= 12): ?>
                    <?= fmt($occ['occupees'], 2) ?> occupées
                <?php endif; ?>
            </div>
        </div>
        <div class="occ-bar-legend">
            <span><span class="dot" style="background:<?= $couleur ?>;"></span> Occupées : <?= fmt($occ['occupees'], 2) ?></span>
            <span><span class="dot" style="background:#e9ecef;"></span> Libres : <?= fmt($occ['libres'], 2) ?></span>
            <span>Total : <?= fmt($occ['capacite']) ?></span>
        </div>
    </div>

    <?php
    // Grille visuelle des emplacements (si la capacité reste lisible).
    $cap  = (int) round($occ['capacite']);
    $occN = (int) round(min($occ['occupees'], $cap));
    ?>
    <?php if ($cap > 0 && $cap <= 300): ?>
        <div class="pallet-grid" title="Chaque case = 1 emplacement palette">
            <?php for ($i = 0; $i < $cap; $i++): ?>
                <span class="cell <?= $i < $occN ? 'cell-occ' : 'cell-free' ?>"
                      style="<?= $i < $occN ? 'background:' . $couleur . ';' : '' ?>"></span>
            <?php endfor; ?>
        </div>
        <p class="hint no-print">Chaque case représente un emplacement palette
            (coloré = occupé, gris = libre).</p>
    <?php endif; ?>

    <?php if ($occ['nb_sans_palette'] > 0): ?>
        <p class="hint"><?= (int) $occ['nb_sans_palette'] ?> article(s) sans « Qté/Palette »
            renseignée ne sont pas comptabilisés dans les palettes occupées.</p>
    <?php endif; ?>

    <!-- Détail par article -->
    <h3 class="section-title">Détail par article</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Article</th>
                <th class="num">Disponible</th>
                <th class="num">Qté/Palette</th>
                <th class="num">Palettes occupées (fraction)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($occ['details'] as $d): ?>
                <tr class="<?= $d['sans_palette'] ? 'row-inactif' : '' ?>">
                    <td><?= htmlspecialchars($d['code']) ?></td>
                    <td><?= htmlspecialchars($d['nom']) ?></td>
                    <td class="num"><?= fmt($d['disponible'], 2) ?></td>
                    <td class="num"><?= $d['sans_palette'] ? '—' : fmt($d['par_palette'], 2) ?></td>
                    <td class="num"><strong><?= $d['sans_palette'] ? '—' : fmt($d['palettes'], 4) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"><strong>Total palettes occupées (fraction)</strong></td>
                <td class="num"><strong><?= fmt($occ['occupees'], 2) ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
