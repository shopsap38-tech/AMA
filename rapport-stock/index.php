<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';

$pageTitle = 'Rapport de suivi de stock';
$filtres   = lire_filtres();

$erreur     = null;
$lignes     = [];
$magasins   = [];
$categories = [];
$marques    = [];
try {
    $toutes     = charger_toutes_lignes();
    $magasins   = liste_valeurs($toutes, 'Magasin');
    $categories = liste_valeurs($toutes, 'U_u_cat');
    $marques    = liste_valeurs($toutes, 'U_u_brand');
    $lignes     = filtrer_et_trier($toutes, $filtres);
} catch (Throwable $e) {
    $erreur = $e->getMessage();
}

$totaux = calculer_totaux($lignes);

/** Construit un lien de tri en conservant les autres filtres. */
function lien_tri(array $filtres, string $colonne): string
{
    $sens = ($filtres['tri'] === $colonne && $filtres['sens'] === 'ASC') ? 'DESC' : 'ASC';
    $q    = array_merge($_GET, ['tri' => $colonne, 'sens' => $sens]);
    return '?' . http_build_query($q);
}

/** Indicateur visuel de tri (▲/▼) pour l'en-tête de colonne. */
function fleche_tri(array $filtres, string $colonne): string
{
    if ($filtres['tri'] !== $colonne) {
        return '';
    }
    return $filtres['sens'] === 'ASC' ? ' ▲' : ' ▼';
}

function fmt_nombre($v, int $dec = 0): string
{
    return number_format((float) $v, $dec, ',', ' ');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="report-head">
    <?php $magAutorises = magasins_autorises(); ?>
    <h2>Suivi de stock<?= $magAutorises ? ' — ' . htmlspecialchars(implode(', ', $magAutorises)) : '' ?></h2>
    <?php if (!$erreur): ?>
        <a class="btn-export no-print"
           href="export.php?<?= htmlspecialchars(http_build_query($_GET)) ?>">Exporter en CSV</a>
    <?php endif; ?>
</div>

<?php if ($erreur): ?>
    <p class="alert alert-error"><strong>Impossible de charger les données :</strong><br>
        <?= nl2br(htmlspecialchars($erreur)) ?></p>
    <?php if (DATA_SOURCE === 'csv'): ?>
        <p class="hint">Mode <strong>CSV</strong> : vérifiez que le fichier
            <code><?= htmlspecialchars(CSV_FILE) ?></code> existe et contient l'export de la vue.
            Voir le README, section « Mode CSV ».</p>
    <?php elseif (DATA_SOURCE === 'ado'): ?>
        <p class="hint">Mode <strong>OLE DB (sans ODBC)</strong> : vérifiez que l'extension
            <code>com_dotnet</code> est activée dans <code>php.ini</code>, le nom de la base
            <code>DB_NAME</code>, les identifiants, et l'accès réseau à
            <?= htmlspecialchars(DB_HOST) ?>. Diagnostic détaillé : <code>test.php</code>.</p>
    <?php elseif (DATA_SOURCE === 'pdo_odbc'): ?>
        <p class="hint">Mode <strong>pdo_odbc (pilote « SQL Server » intégré)</strong> :
            vérifiez que l'extension <code>pdo_odbc</code> est activée dans <code>php.ini</code>,
            le nom de la base <code>DB_NAME</code>, les identifiants, et l'accès réseau à
            <?= htmlspecialchars(DB_HOST) ?>. Diagnostic détaillé : <code>test.php</code>.</p>
    <?php else: ?>
        <p class="hint">Mode <strong>SQL Server</strong> : vérifiez les paramètres dans
            <code>config/database.php</code> (nom de la base <code>DB_NAME</code>, pilote ODBC/PDO
            installé, accès réseau à <?= htmlspecialchars(DB_HOST) ?>).</p>
    <?php endif; ?>
<?php else: ?>

    <form class="filters no-print" method="get" action="index.php">
        <?php if (count($magasins) > 1): ?>
        <div class="field">
            <label for="magasin">Magasin</label>
            <select name="magasin" id="magasin">
                <option value="">Tous</option>
                <?php foreach ($magasins as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>"
                        <?= $filtres['magasin'] === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="field">
            <label for="categorie">Catégorie</label>
            <select name="categorie" id="categorie">
                <option value="">Toutes</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>"
                        <?= $filtres['categorie'] === $c ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="marque">Marque</label>
            <select name="marque" id="marque">
                <option value="">Toutes</option>
                <?php foreach ($marques as $mq): ?>
                    <option value="<?= htmlspecialchars($mq) ?>"
                        <?= $filtres['marque'] === $mq ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mq) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="recherche">Recherche (code, nom, code-barres)</label>
            <input type="text" name="recherche" id="recherche"
                   value="<?= htmlspecialchars($filtres['recherche']) ?>"
                   placeholder="Ex. : A0001, Ciment…">
        </div>
        <div class="field field-check">
            <label>
                <input type="checkbox" name="masquer_inactifs" value="1"
                    <?= $filtres['masquer_inactifs'] ? 'checked' : '' ?>>
                Masquer les articles inactifs
            </label>
        </div>
        <div class="field field-actions">
            <button type="submit">Filtrer</button>
            <a class="btn-reset" href="index.php">Réinitialiser</a>
        </div>
    </form>

    <div class="summary">
        <div class="summary-card">
            <span class="summary-label">Articles</span>
            <span class="summary-value"><?= fmt_nombre($totaux['nb']) ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Total disponible</span>
            <span class="summary-value"><?= fmt_nombre($totaux['disponible'], 2) ?></span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Valeur totale</span>
            <span class="summary-value"><?= fmt_nombre($totaux['value'], 2) ?></span>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th><a href="<?= lien_tri($filtres, 'Magasin') ?>">Magasin<?= fleche_tri($filtres, 'Magasin') ?></a></th>
                <th><a href="<?= lien_tri($filtres, 'Item Code') ?>">Code<?= fleche_tri($filtres, 'Item Code') ?></a></th>
                <th><a href="<?= lien_tri($filtres, 'Item Name') ?>">Article<?= fleche_tri($filtres, 'Item Name') ?></a></th>
                <th class="num"><a href="<?= lien_tri($filtres, 'Disponible') ?>">Disponible<?= fleche_tri($filtres, 'Disponible') ?></a></th>
                <th>UoM</th>
                <th>Code-barres</th>
                <th class="num"><a href="<?= lien_tri($filtres, 'Poids') ?>">Poids<?= fleche_tri($filtres, 'Poids') ?></a></th>
                <th class="num"><a href="<?= lien_tri($filtres, 'Price') ?>">Prix<?= fleche_tri($filtres, 'Price') ?></a></th>
                <th class="num"><a href="<?= lien_tri($filtres, 'Value') ?>">Valeur<?= fleche_tri($filtres, 'Value') ?></a></th>
                <th class="num"><a href="<?= lien_tri($filtres, 'U_u_forcast') ?>">Prévision<?= fleche_tri($filtres, 'U_u_forcast') ?></a></th>
                <th class="num"><a href="<?= lien_tri($filtres, 'U_Qte_Palette') ?>">Qté/Palette<?= fleche_tri($filtres, 'U_Qte_Palette') ?></a></th>
                <th><a href="<?= lien_tri($filtres, 'U_u_cat') ?>">Catégorie<?= fleche_tri($filtres, 'U_u_cat') ?></a></th>
                <th><a href="<?= lien_tri($filtres, 'U_u_brand') ?>">Marque<?= fleche_tri($filtres, 'U_u_brand') ?></a></th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lignes)): ?>
                <tr><td colspan="14">Aucune ligne ne correspond aux critères.</td></tr>
            <?php endif; ?>
            <?php foreach ($lignes as $l): ?>
                <?php $inactif = isset($l['InActif']) && strtoupper((string) $l['InActif']) === 'Y'; ?>
                <tr class="<?= $inactif ? 'row-inactif' : '' ?>">
                    <td><?= htmlspecialchars((string) $l['Magasin']) ?></td>
                    <td><?= htmlspecialchars((string) $l['Item Code']) ?></td>
                    <td><?= htmlspecialchars((string) $l['Item Name']) ?></td>
                    <td class="num"><?= fmt_nombre($l['Disponible'], 2) ?></td>
                    <td><?= htmlspecialchars((string) $l['UoM']) ?></td>
                    <td><?= htmlspecialchars((string) $l['CodeBars']) ?></td>
                    <td class="num"><?= fmt_nombre($l['Poids'], 2) ?></td>
                    <td class="num"><?= fmt_nombre($l['Price'], 2) ?></td>
                    <td class="num"><?= fmt_nombre($l['Value'], 2) ?></td>
                    <td class="num"><?= fmt_nombre($l['U_u_forcast'], 2) ?></td>
                    <td class="num"><?= fmt_nombre($l['U_Qte_Palette'], 2) ?></td>
                    <td><?= htmlspecialchars((string) ($l['U_u_cat'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($l['U_u_brand'] ?? '')) ?></td>
                    <td>
                        <?php if ($inactif): ?>
                            <span class="badge badge-danger">Inactif</span>
                        <?php else: ?>
                            <span class="badge badge-ok">Actif</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($lignes)): ?>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Totaux (<?= fmt_nombre($totaux['nb']) ?> articles)</strong></td>
                <td class="num"><strong><?= fmt_nombre($totaux['disponible'], 2) ?></strong></td>
                <td colspan="4"></td>
                <td class="num"><strong><?= fmt_nombre($totaux['value'], 2) ?></strong></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
