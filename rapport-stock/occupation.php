<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';

$pageTitle = 'Occupation du magasin - Dashboard';
$refresh = 60;

$erreur = null;
$occ = null;
try {
    $lignes = charger_toutes_lignes();
    $occ = calculer_occupation($lignes, (float) CAPACITE_PALETTES);
} catch (Throwable $e) {
    $erreur = $e->getMessage();
}

function fmt($v, int $dec = 0): string {
    return number_format((float) $v, $dec, ',', ' ');
}

function couleur_taux(float $taux): string {
    if ($taux >= 90) return '#bb0000'; // SAP Error Red
    if ($taux >= 75) return '#e78c07'; // SAP Warning Orange
    if ($taux >= 50) return '#f0ab00'; // SAP Alert Yellow
    return '#107e3e'; // SAP Success Green
}

function getStatusType(float $taux): string {
    if ($taux >= 90) return 'Error';
    if ($taux >= 75) return 'Warning';
    if ($taux >= 50) return 'Information';
    return 'Success';
}

function getStatusLabel(float $taux): string {
    if ($taux >= 90) return 'Critique';
    if ($taux >= 75) return 'Élevé';
    if ($taux >= 50) return 'Modéré';
    return 'Normal';
}

function getStatusText(float $taux): string {
    if ($taux >= 90) return 'Capacité presque atteinte - Action requise';
    if ($taux >= 75) return 'Niveau élevé - Surveillance recommandée';
    if ($taux >= 50) return 'Occupation modérée - Fonctionnement normal';
    return 'Espace suffisant - Aucune action nécessaire';
}

$magasin = MAGASIN_FILTRE !== '' ? MAGASIN_FILTRE : 'Tous les magasins';

// Valeurs par défaut : on ne calcule les statuts/graphiques QUE si les données
// ont bien été chargées. En cas d'erreur de connexion, $occ est null et l'on
// affiche le message d'erreur SAP plus bas (au lieu d'un plantage fatal).
$couleur = '#107e3e';
$statusType = 'Success';
$statusLabel = 'Normal';
$pourcentageOccupe = 0;
$pourcentageLibre = 100;

if (!$erreur && $occ !== null) {
    $taux = (float) $occ['taux_occupation'];
    $couleur = couleur_taux($taux);
    $statusType = getStatusType($taux);
    $statusLabel = getStatusLabel($taux);

    // Données pour le graphique donut
    $pourcentageOccupe = min($taux, 100);
    $pourcentageLibre = max(0, 100 - $pourcentageOccupe);
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($refresh > 0 && !$erreur): ?>
<script>
setTimeout(function() { location.reload(); }, <?= (int) $refresh * 1000 ?>);
</script>
<?php endif; ?>

<script>
// Fonction de recherche pour le tableau des articles
function filterArticles() {
    const input = document.getElementById('searchArticles');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('articlesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    let visibleCount = 0;

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const codeCell = row.getElementsByTagName('td')[0];
        const nomCell = row.getElementsByTagName('td')[1];

        if (codeCell || nomCell) {
            const codeText = codeCell ? codeCell.textContent || codeCell.innerText : '';
            const nomText = nomCell ? nomCell.textContent || nomCell.innerText : '';

            if (codeText.toUpperCase().indexOf(filter) > -1 ||
                nomText.toUpperCase().indexOf(filter) > -1) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    }

    // Mettre à jour le compteur d'articles visibles
    const counter = document.getElementById('articlesCount');
    if (counter) {
        const total = rows.length;
        if (filter === '') {
            counter.textContent = total + ' article(s)';
        } else {
            counter.textContent = visibleCount + ' article(s) sur ' + total;
        }
    }
}

// Fonction pour effacer la recherche
function clearSearch() {
    const input = document.getElementById('searchArticles');
    input.value = '';
    filterArticles();
    input.focus();
}

// Ajouter un écouteur pour la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const input = document.getElementById('searchArticles');
        if (document.activeElement === input) {
            clearSearch();
        }
    }
});
</script>

<style>
/* SAP Fiori 3 Design System */
:root {
    /* SAP Fiori 3 Base Colors */
    --sapBrandColor: #0a6ed1;
    --sapHighlightColor: #0a6ed1;
    --sapBaseColor: #f7f7f7;
    --sapShellColor: #354a5f;
    --sapBackgroundColor: #f7f7f7;

    /* SAP Fiori 3 Font */
    --sapFontFamily: "72", "72full", "72-Bold", "72-Boldfull", Arial, Helvetica, sans-serif;
    --sapFontSize: 0.875rem;
    --sapFontSmallSize: 0.75rem;

    /* SAP Fiori 3 Text Colors */
    --sapTextColor: #32363a;
    --sapTitleColor: #32363a;
    --sapLabelColor: #6a6d70;
    --sapLinkColor: #0a6ed1;

    /* SAP Fiori 3 Semantic Colors */
    --sapNegativeColor: #bb0000;
    --sapCriticalColor: #e78c07;
    --sapPositiveColor: #107e3e;
    --sapInformativeColor: #0a6ed1;
    --sapNeutralColor: #6a6d70;

    /* SAP Fiori 3 Background Colors */
    --sapErrorBackground: #ffeaf0;
    --sapWarningBackground: #fff8d6;
    --sapSuccessBackground: #f5fae5;
    --sapInformationBackground: #e5f0fa;

    /* SAP Fiori 3 Border Colors */
    --sapErrorBorderColor: #bb0000;
    --sapWarningBorderColor: #e78c07;
    --sapSuccessBorderColor: #107e3e;
    --sapInformationBorderColor: #0a6ed1;

    /* SAP Fiori 3 Object Colors */
    --sapObjectHeader_Background: #ffffff;
    --sapTile_Background: #ffffff;
    --sapTile_BorderColor: #d9d9d9;
    --sapTile_TitleTextColor: #32363a;

    /* SAP Fiori 3 Spacing */
    --sapContent_Padding: 1rem;
    --sapContent_ElementSpacing: 0.5rem;

    /* SAP Fiori 3 Shadows */
    --sapContent_Shadow0: 0 0 0 1px rgba(0,0,0,0.1);
    --sapContent_Shadow1: 0 0 2px rgba(0,0,0,0.1), 0 2px 8px rgba(0,0,0,0.1);
    --sapContent_Shadow2: 0 0 4px rgba(0,0,0,0.1), 0 4px 16px rgba(0,0,0,0.1);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--sapFontFamily);
    font-size: var(--sapFontSize);
    color: var(--sapTextColor);
    background: var(--sapBackgroundColor);
    line-height: 1.4;
    -webkit-font-smoothing: antialiased;
}

/* SAP Fiori 3 Shell Bar */
.sapShellBar {
    background: var(--sapShellColor);
    color: white;
    padding: 0 1.5rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--sapContent_Shadow1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.sapShellBar .logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.sapShellBar .actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sapShellBar .sapBtn {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.8125rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    text-decoration: none;
    font-weight: 500;
}

.sapShellBar .sapBtn:hover {
    background: rgba(255,255,255,0.15);
    border-color: rgba(255,255,255,0.5);
}

.sapShellBar .timestamp {
    font-size: var(--sapFontSmallSize);
    opacity: 0.85;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Main Content */
.sapContent {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
}

/* SAP Fiori 3 Object Header */
.sapObjectHeader {
    background: var(--sapObjectHeader_Background);
    border-radius: 0.25rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--sapContent_Shadow0);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1rem;
}

.sapObjectHeader .title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--sapTitleColor);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.sapObjectHeader .subtitle {
    font-size: var(--sapFontSmallSize);
    color: var(--sapLabelColor);
    margin-top: 0.5rem;
}

.sapObjectHeader .infoBlock {
    text-align: right;
    font-size: var(--sapFontSmallSize);
    color: var(--sapLabelColor);
    line-height: 1.6;
}

/* SAP Fiori 3 Object Status */
.sapObjectStatus {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.5;
}

.sapObjectStatus.sapObjectStatusError {
    background: var(--sapErrorBackground);
    color: var(--sapNegativeColor);
    border: 1px solid var(--sapErrorBorderColor);
}

.sapObjectStatus.sapObjectStatusWarning {
    background: var(--sapWarningBackground);
    color: var(--sapCriticalColor);
    border: 1px solid var(--sapWarningBorderColor);
}

.sapObjectStatus.sapObjectStatusSuccess {
    background: var(--sapSuccessBackground);
    color: var(--sapPositiveColor);
    border: 1px solid var(--sapSuccessBorderColor);
}

.sapObjectStatus.sapObjectStatusInformation {
    background: var(--sapInformationBackground);
    color: var(--sapInformativeColor);
    border: 1px solid var(--sapInformationBorderColor);
}

.sapObjectStatus .sapObjectStatusIcon {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

/* SAP Fiori 3 Message Strip */
.sapMMessageStrip {
    padding: 0.75rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: var(--sapFontSize);
    box-shadow: var(--sapContent_Shadow0);
}

.sapMMessageStrip.sapMMessageStripError {
    background: var(--sapErrorBackground);
    border-left: 4px solid var(--sapErrorBorderColor);
    color: var(--sapNegativeColor);
}

.sapMMessageStrip.sapMMessageStripWarning {
    background: var(--sapWarningBackground);
    border-left: 4px solid var(--sapWarningBorderColor);
    color: var(--sapCriticalColor);
}

.sapMMessageStrip.sapMMessageStripInformation {
    background: var(--sapInformationBackground);
    border-left: 4px solid var(--sapInformationBorderColor);
    color: var(--sapInformativeColor);
}

.sapMMessageStrip .sapMMessageStripIcon {
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

/* SAP Fiori 3 Tiles */
.sapMTileContainer {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.sapMNumericTile {
    background: var(--sapTile_Background);
    border-radius: 0.25rem;
    padding: 1.25rem;
    box-shadow: var(--sapContent_Shadow0);
    border: 1px solid var(--sapTile_BorderColor);
    transition: box-shadow 0.3s;
}

.sapMNumericTile:hover {
    box-shadow: var(--sapContent_Shadow2);
}

.sapMNumericTile .sapMNumericTileHeader {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.sapMNumericTile .sapMNumericTileTitle {
    font-size: var(--sapFontSmallSize);
    text-transform: uppercase;
    color: var(--sapLabelColor);
    letter-spacing: 0.05em;
    font-weight: 700;
}

.sapMNumericTile .sapMNumericTileIcon {
    font-size: 1.25rem;
    opacity: 0.6;
}

.sapMNumericTile .sapMNumericTileValue {
    font-size: 2rem;
    font-weight: 700;
    color: var(--sapTextColor);
    line-height: 1.2;
    margin-bottom: 0.25rem;
    font-family: "72-Bold", "72-Boldfull", Arial, Helvetica, sans-serif;
}

.sapMNumericTile .sapMNumericTileValueError { color: var(--sapNegativeColor); }
.sapMNumericTile .sapMNumericTileValueWarning { color: var(--sapCriticalColor); }
.sapMNumericTile .sapMNumericTileValueSuccess { color: var(--sapPositiveColor); }
.sapMNumericTile .sapMNumericTileValueInformation { color: var(--sapInformativeColor); }

.sapMNumericTile .sapMNumericTileFooter {
    font-size: var(--sapFontSmallSize);
    color: var(--sapLabelColor);
}

/* SAP Fiori 3 Section */
.sapMResponsiveGrid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
}

.sapMSection {
    background: var(--sapTile_Background);
    border-radius: 0.25rem;
    box-shadow: var(--sapContent_Shadow0);
    border: 1px solid var(--sapTile_BorderColor);
}

.sapMSectionHeader {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e5e5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
    border-radius: 0.25rem 0.25rem 0 0;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.sapMSectionHeader .sapMSectionTitle {
    font-size: 1rem;
    font-weight: 700;
    color: var(--sapTitleColor);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sapMSectionContent {
    padding: 1.5rem;
}

/* Donut Chart */
.donutContainer {
    display: flex;
    align-items: center;
    gap: 3rem;
    flex-wrap: wrap;
    justify-content: center;
}

.donutChart {
    position: relative;
    width: 200px;
    height: 200px;
}

.donutChart svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.donutChart .center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.donutChart .center .percentage {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    font-family: "72-Bold", "72-Boldfull", Arial, Helvetica, sans-serif;
}

.donutChart .center .label {
    font-size: var(--sapFontSmallSize);
    color: var(--sapLabelColor);
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Stats Grid */
.statsGrid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    flex: 1;
    min-width: 300px;
}

.statCard {
    padding: 1rem;
    background: #f5f6f7;
    border-radius: 0.25rem;
    border: 1px solid #e5e5e5;
}

.statCard .statLabel {
    font-size: 0.6875rem;
    text-transform: uppercase;
    color: var(--sapLabelColor);
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
    font-weight: 700;
}

.statCard .statValue {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--sapTextColor);
    font-family: "72-Bold", "72-Boldfull", Arial, Helvetica, sans-serif;
}

.statCard .statDetail {
    font-size: 0.6875rem;
    color: var(--sapLabelColor);
    margin-top: 0.25rem;
}

/* Progress Bar */
.progressContainer {
    margin: 1.5rem 0 0.5rem 0;
}

.progressLabel {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: var(--sapFontSmallSize);
    color: var(--sapLabelColor);
}

.progressBar {
    height: 1.25rem;
    background: #e5e5e5;
    border-radius: 0.125rem;
    overflow: hidden;
}

.progressFill {
    height: 100%;
    border-radius: 0.125rem;
    transition: width 0.6s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 0.5rem;
    min-width: 2.5rem;
}

.progressFill .progressText {
    color: white;
    font-size: 0.6875rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.progressMarkers {
    display: flex;
    justify-content: space-between;
    margin-top: 0.375rem;
    font-size: 0.625rem;
    color: var(--sapLabelColor);
}

/* SAP Fiori 3 Table */
.sapMTable {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.sapMTable thead th {
    background: #f2f2f2;
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 700;
    color: var(--sapLabelColor);
    text-transform: uppercase;
    font-size: 0.6875rem;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #d9d9d9;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

.sapMTable tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e5e5;
    color: var(--sapTextColor);
}

.sapMTable tbody tr:hover {
    background: #f0f8ff;
}

.sapMTable tbody tr.sapMTableRowInactive {
    opacity: 0.6;
    background: #fafafa;
}

.sapMTable .sapMTableAlignEnd {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.sapMTable tfoot td {
    font-weight: 700;
    border-top: 2px solid #d9d9d9;
    padding: 0.75rem 1rem;
    background: #fafafa;
}

.sapMTableScrollContainer {
    max-height: 500px;
    overflow-y: auto;
    border-radius: 0 0 0.25rem 0.25rem;
}

/* Status Indicator */
.sapMStatusIndicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.sapMStatusIndicator .sapMStatusDot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    flex-shrink: 0;
}

.sapMStatusDotError { background: var(--sapNegativeColor); }
.sapMStatusDotWarning { background: var(--sapCriticalColor); }
.sapMStatusDotSuccess { background: var(--sapPositiveColor); }
.sapMStatusDotNeutral { background: #bbb; }

/* Style de la barre de recherche */
.searchContainer {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #f5f6f7;
    border-radius: 0.25rem;
    padding: 0.125rem;
    border: 1px solid #d9d9d9;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.searchContainer:focus-within {
    border-color: var(--sapBrandColor);
    box-shadow: 0 0 0 1px var(--sapBrandColor);
}

.searchContainer .searchIcon {
    padding: 0 0.25rem 0 0.5rem;
    color: var(--sapLabelColor);
}

.searchContainer input {
    border: none;
    background: transparent;
    padding: 0.375rem 0.5rem;
    font-family: var(--sapFontFamily);
    font-size: var(--sapFontSize);
    color: var(--sapTextColor);
    width: 200px;
    outline: none;
}

.searchContainer input:focus {
    background: white;
    border-radius: 0.25rem;
}

.searchContainer input::placeholder {
    color: var(--sapLabelColor);
    opacity: 0.7;
}

.searchContainer .clearBtn {
    background: transparent;
    border: none;
    padding: 0 0.5rem;
    cursor: pointer;
    color: var(--sapLabelColor);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.searchContainer .clearBtn:hover {
    color: var(--sapTextColor);
}

/* Animation pour les résultats de recherche */
.sapMTable tbody tr {
    transition: opacity 0.2s ease;
}

.sapMTable tbody tr[style*="display: none"] {
    opacity: 0;
}

.articlesCounter {
    font-size: 0.8125rem;
    color: var(--sapLabelColor);
    white-space: nowrap;
}

/* SAP Fiori 3 Responsive */
@media (max-width: 1024px) {
    .sapMTileContainer {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .sapObjectHeader {
        flex-direction: column;
    }

    .sapMTileContainer {
        grid-template-columns: repeat(2, 1fr);
    }

    .donutContainer {
        flex-direction: column;
        align-items: center;
    }

    .statsGrid {
        width: 100%;
    }

    .sapMSectionHeader {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .sapMSectionHeader > div:last-child {
        flex-wrap: wrap;
    }

    .searchContainer input {
        width: 100%;
        min-width: 120px;
    }

    .articlesCounter {
        white-space: normal;
    }
}

@media (max-width: 480px) {
    .sapMTileContainer {
        grid-template-columns: 1fr;
    }

    .statsGrid {
        grid-template-columns: 1fr;
    }

    .sapShellBar {
        padding: 0 1rem;
    }

    .sapContent {
        padding: 1rem;
    }

    .searchContainer {
        flex: 1;
    }

    .searchContainer input {
        width: 100%;
    }
}
</style>

<!-- SAP Fiori 3 Shell Bar -->
<div class="sapShellBar">
    <div class="logo">
        <span>📦</span>
        <span>Gestion d'Entrepôt</span>
    </div>
    <div class="actions">
        <span class="timestamp">🕐 <?= date('d/m/Y H:i') ?></span>
        <a href="occupation.php" class="sapBtn">↻ Actualiser</a>
    </div>
</div>

<div class="sapContent">

    <!-- SAP Fiori 3 Object Header -->
    <div class="sapObjectHeader">
        <div>
            <div class="title">
                Occupation du magasin
                <?php if (!$erreur): ?>
                    <span class="sapObjectStatus sapObjectStatus<?= $statusType ?>">
                        <span class="sapObjectStatusIcon sapMStatusDot<?= $statusType ?>"></span>
                        <?= $statusLabel ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="subtitle">
                <?= htmlspecialchars($magasin) ?><?php if (!$erreur && $occ !== null): ?> • <?= getStatusText((float) $occ['taux_occupation']) ?><?php endif; ?>
            </div>
        </div>
        <?php if (!$erreur): ?>
            <div class="infoBlock">
                Mise à jour : <?= date('H:i:s') ?>
                <?php if ($refresh > 0): ?>
                    <br>Auto-refresh : <?= $refresh ?>s
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($erreur): ?>
        <div class="sapMMessageStrip sapMMessageStripError">
            <span class="sapMMessageStripIcon">⛔</span>
            <div>
                <strong>Erreur de chargement des données</strong><br>
                <?= nl2br(htmlspecialchars($erreur)) ?>
            </div>
        </div>
    <?php elseif (CAPACITE_PALETTES <= 0): ?>
        <div class="sapMMessageStrip sapMMessageStripError">
            <span class="sapMMessageStripIcon">⚠️</span>
            <div>
                <strong>Configuration manquante</strong><br>
                La capacité du magasin (<code>CAPACITE_PALETTES</code>) n'est pas définie dans <code>config/database.php</code>.
            </div>
        </div>
    <?php else: ?>

    <?php if ($occ['depassement']): ?>
        <div class="sapMMessageStrip sapMMessageStripError">
            <span class="sapMMessageStripIcon">⚠️</span>
            <div>
                <strong>Dépassement de capacité</strong><br>
                La capacité est dépassée de <strong><?= fmt($occ['occupees'] - $occ['capacite']) ?> palette(s)</strong>.
                Action immédiate requise.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($occ['nb_sans_palette'] > 0): ?>
        <div class="sapMMessageStrip sapMMessageStripInformation">
            <span class="sapMMessageStripIcon">ℹ️</span>
            <div>
                <strong><?= (int) $occ['nb_sans_palette'] ?> article(s)</strong> sans configuration de palette
            </div>
        </div>
    <?php endif; ?>

    <!-- SAP Fiori 3 Numeric Tiles -->
    <div class="sapMTileContainer">
        <!-- Taux d'occupation -->
        <div class="sapMNumericTile">
            <div class="sapMNumericTileHeader">
                <span class="sapMNumericTileTitle">Taux d'occupation</span>
                <span class="sapMNumericTileIcon">📊</span>
            </div>
            <div class="sapMNumericTileValue sapMNumericTileValue<?= $statusType ?>">
                <?= fmt($occ['taux_occupation'], 1) ?>%
            </div>
            <div class="sapMNumericTileFooter">
                <?= $statusLabel ?>
            </div>
        </div>

        <!-- Capacité totale -->
        <div class="sapMNumericTile">
            <div class="sapMNumericTileHeader">
                <span class="sapMNumericTileTitle">Capacité totale</span>
                <span class="sapMNumericTileIcon">🏗️</span>
            </div>
            <div class="sapMNumericTileValue sapMNumericTileValueInformation">
                <?= fmt($occ['capacite']) ?>
            </div>
            <div class="sapMNumericTileFooter">
                Emplacements palette
            </div>
        </div>

        <!-- Palettes occupées -->
        <div class="sapMNumericTile">
            <div class="sapMNumericTileHeader">
                <span class="sapMNumericTileTitle">Palettes occupées</span>
                <span class="sapMNumericTileIcon">📦</span>
            </div>
            <div class="sapMNumericTileValue <?= $occ['taux_occupation'] >= 90 ? 'sapMNumericTileValueError' : ($occ['taux_occupation'] >= 75 ? 'sapMNumericTileValueWarning' : '') ?>">
                <?= fmt($occ['occupees'], 2) ?>
            </div>
            <div class="sapMNumericTileFooter">
                Sur <?= fmt($occ['capacite']) ?> totales
            </div>
        </div>

        <!-- Espace disponible -->
        <div class="sapMNumericTile">
            <div class="sapMNumericTileHeader">
                <span class="sapMNumericTileTitle">Espace disponible</span>
                <span class="sapMNumericTileIcon">✓</span>
            </div>
            <div class="sapMNumericTileValue sapMNumericTileValueSuccess">
                <?= fmt($occ['libres'], 2) ?>
            </div>
            <div class="sapMNumericTileFooter">
                <?= fmt($occ['taux_disponible'], 1) ?>% de la capacité
            </div>
        </div>
    </div>

    <!-- SAP Fiori 3 Donut Chart Section -->
    <div class="sapMSection">
        <div class="sapMSectionHeader">
            <div class="sapMSectionTitle">📐 Répartition de la capacité</div>
            <span style="font-size:0.8125rem;color:var(--sapLabelColor);">
                <?= fmt($occ['occupees'], 2) ?> / <?= fmt($occ['capacite']) ?> palettes
            </span>
        </div>
        <div class="sapMSectionContent">
            <div class="donutContainer">
                <!-- Donut Chart SVG -->
                <div class="donutChart">
                    <svg viewBox="0 0 36 36">
                        <!-- Fond -->
                        <circle cx="18" cy="18" r="15.915" fill="none" stroke="#e5e5e5" stroke-width="3.5"/>
                        <!-- Partie occupée -->
                        <?php if ($pourcentageOccupe > 0): ?>
                        <circle cx="18" cy="18" r="15.915" fill="none"
                            stroke="<?= $couleur ?>"
                            stroke-width="3.5"
                            stroke-dasharray="<?= $pourcentageOccupe ?>, <?= 100 - $pourcentageOccupe ?>"
                            stroke-linecap="round"/>
                        <?php endif; ?>
                    </svg>
                    <div class="center">
                        <div class="percentage" style="color:<?= $couleur ?>;"><?= fmt($occ['taux_occupation'], 1) ?>%</div>
                        <div class="label">Occupé</div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="statsGrid">
                    <div class="statCard">
                        <div class="statLabel">📦 Occupé</div>
                        <div class="statValue" style="color:<?= $couleur ?>;"><?= fmt($occ['occupees'], 2) ?></div>
                        <div class="statDetail">palettes</div>
                    </div>
                    <div class="statCard">
                        <div class="statLabel">✓ Libre</div>
                        <div class="statValue" style="color:var(--sapPositiveColor);"><?= fmt($occ['libres'], 2) ?></div>
                        <div class="statDetail">palettes (<?= fmt($occ['taux_disponible'], 1) ?>%)</div>
                    </div>
                    <div class="statCard">
                        <div class="statLabel">🏗️ Capacité</div>
                        <div class="statValue"><?= fmt($occ['capacite']) ?></div>
                        <div class="statDetail">emplacements</div>
                    </div>
                    <div class="statCard">
                        <div class="statLabel">📋 Articles</div>
                        <div class="statValue"><?= count($occ['details']) ?></div>
                        <div class="statDetail">références</div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progressContainer">
                <div class="progressLabel">
                    <span>Occupation</span>
                    <span><?= fmt($occ['occupees'], 2) ?> / <?= fmt($occ['capacite']) ?> palettes</span>
                </div>
                <div class="progressBar">
                    <div class="progressFill" style="width:<?= $pourcentageOccupe ?>%;background:<?= $couleur ?>;">
                        <?php if ($pourcentageOccupe >= 15): ?>
                            <span class="progressText"><?= fmt($occ['taux_occupation'], 1) ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="progressMarkers">
                    <span>0%</span>
                    <span>25%</span>
                    <span style="color:var(--sapCriticalColor);">50%</span>
                    <span style="color:var(--sapCriticalColor);">75%</span>
                    <span style="color:var(--sapNegativeColor);">90%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SAP Fiori 3 Table Section avec barre de recherche -->
    <div class="sapMSection">
        <div class="sapMSectionHeader">
            <div class="sapMSectionTitle">📋 Détail par article</div>
            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <!-- Barre de recherche -->
                <div class="searchContainer">
                    <span class="searchIcon">🔍</span>
                    <input
                        type="text"
                        id="searchArticles"
                        placeholder="Rechercher un article..."
                        onkeyup="filterArticles()"
                    >
                    <button
                        class="clearBtn"
                        onclick="clearSearch()"
                        title="Effacer la recherche"
                    >
                        ✕
                    </button>
                </div>
                <span id="articlesCount" class="articlesCounter">
                    <?= count($occ['details']) ?> article(s)
                </span>
            </div>
        </div>
        <div class="sapMTableScrollContainer">
            <table class="sapMTable" id="articlesTable">
                <thead>
                    <tr>
                        <th>Code article</th>
                        <th>Désignation</th>
                        <th class="sapMTableAlignEnd">Stock disponible</th>
                        <th class="sapMTableAlignEnd">Qté / Palette</th>
                        <th class="sapMTableAlignEnd">Palettes occupées</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($occ['details'] as $d): ?>
                        <tr class="<?= $d['sans_palette'] ? 'sapMTableRowInactive' : '' ?>">
                            <td>
                                <span class="sapMStatusIndicator">
                                    <?php if ($d['sans_palette']): ?>
                                        <span class="sapMStatusDot sapMStatusDotNeutral"></span>
                                    <?php else: ?>
                                        <span class="sapMStatusDot sapMStatusDotSuccess"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($d['code']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($d['nom']) ?></td>
                            <td class="sapMTableAlignEnd"><?= fmt($d['disponible'], 2) ?></td>
                            <td class="sapMTableAlignEnd">
                                <?= $d['sans_palette'] ? '—' : fmt($d['par_palette'], 2) ?>
                            </td>
                            <td class="sapMTableAlignEnd">
                                <?php if ($d['sans_palette']): ?>
                                    <span style="color:#999;">—</span>
                                <?php else: ?>
                                    <strong><?= fmt($d['palettes'], 4) ?></strong>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><strong>Total palettes occupées (fraction)</strong></td>
                        <td class="sapMTableAlignEnd"><strong><?= fmt($occ['occupees'], 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
