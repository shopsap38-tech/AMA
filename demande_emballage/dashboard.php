<?php
require_once __DIR__ . '/config/config.php';
require_role(['administrateur', 'facturation', 'preparateur']);

$pageTitle = 'Tableau de bord';

// ---------------------------------------------------------
//  Indicateurs MTD (Month To Date) — demandes clôturées
// ---------------------------------------------------------
$mtd = $pdo->query(
    "SELECT
        SUM(CASE WHEN delai_minutes <= " . SLA_SEUIL . " THEN 1 ELSE 0 END) AS dans_delai,
        SUM(CASE WHEN delai_minutes >  " . SLA_SEUIL . " THEN 1 ELSE 0 END) AS hors_delai,
        COUNT(*) AS total
     FROM demandes
     WHERE statut = 'fait'
       AND delai_minutes IS NOT NULL
       AND YEAR(cloture_le)  = YEAR(CURRENT_DATE)
       AND MONTH(cloture_le) = MONTH(CURRENT_DATE)"
)->fetch();

$dansDelai = (int) ($mtd['dans_delai'] ?? 0);
$horsDelai = (int) ($mtd['hors_delai'] ?? 0);
$totalMtd  = (int) ($mtd['total'] ?? 0);
$tauxRespect = $totalMtd > 0 ? round($dansDelai / $totalMtd * 100) : 0;

// Compteurs d'états courants (charge de travail)
$parStatut = $pdo->query(
    "SELECT statut, COUNT(*) AS n FROM demandes GROUP BY statut"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$nbAttente = (int) ($parStatut['en_attente'] ?? 0);
$nbRoute   = (int) ($parStatut['en_route'] ?? 0);

// ---------------------------------------------------------
//  Tendance quotidienne du mois en cours
// ---------------------------------------------------------
$trend = $pdo->query(
    "SELECT DATE(cloture_le) AS jour,
            SUM(CASE WHEN delai_minutes <= " . SLA_SEUIL . " THEN 1 ELSE 0 END) AS dans_delai,
            SUM(CASE WHEN delai_minutes >  " . SLA_SEUIL . " THEN 1 ELSE 0 END) AS hors_delai
     FROM demandes
     WHERE statut = 'fait'
       AND delai_minutes IS NOT NULL
       AND YEAR(cloture_le)  = YEAR(CURRENT_DATE)
       AND MONTH(cloture_le) = MONTH(CURRENT_DATE)
     GROUP BY DATE(cloture_le)
     ORDER BY jour ASC"
)->fetchAll();

$trendLabels = [];
$trendDans   = [];
$trendHors   = [];
foreach ($trend as $row) {
    $trendLabels[] = date('d/m', strtotime($row['jour']));
    $trendDans[]   = (int) $row['dans_delai'];
    $trendHors[]   = (int) $row['hors_delai'];
}

$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
    . '<script>window.DE_DASH = ' . json_encode([
        'dansDelai'   => $dansDelai,
        'horsDelai'   => $horsDelai,
        'trendLabels' => $trendLabels,
        'trendDans'   => $trendDans,
        'trendHors'   => $trendHors,
        'seuil'       => SLA_SEUIL,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>'
    . '<script src="' . e(BASE_URL) . '/assets/js/dashboard.js"></script>';

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4"><i class="bi bi-bar-chart-line text-primary"></i> Tableau de bord</h1>

<!-- Cartes indicateurs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card bg-success shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= $dansDelai ?></div>
                    <div class="stat-label">≤ <?= SLA_SEUIL ?> min (MTD)</div>
                </div>
                <i class="bi bi-check2-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card bg-danger shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= $horsDelai ?></div>
                    <div class="stat-label">&gt; <?= SLA_SEUIL ?> min (MTD)</div>
                </div>
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card bg-primary shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= $tauxRespect ?>%</div>
                    <div class="stat-label">Respect du délai (MTD)</div>
                </div>
                <i class="bi bi-speedometer2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card bg-secondary shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= $nbAttente + $nbRoute ?></div>
                    <div class="stat-label"><?= $nbAttente ?> en attente · <?= $nbRoute ?> en route</div>
                </div>
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Nombre de demandes MTD</div>
            <div class="card-body">
                <?php if ($totalMtd === 0): ?>
                    <p class="text-muted mb-0">Aucune demande clôturée ce mois-ci.</p>
                <?php else: ?>
                    <canvas id="chartBar" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Tendance du respect du délai</div>
            <div class="card-body">
                <?php if (!$trendLabels): ?>
                    <p class="text-muted mb-0">Pas encore de données pour tracer la tendance.</p>
                <?php else: ?>
                    <canvas id="chartLine" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
