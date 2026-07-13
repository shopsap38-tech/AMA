<?php

/**
 * Fonctions utilitaires et requêtes statistiques partagées par les pages.
 */

/** Libellés lisibles pour les états des chariots (terminologie du suivi : OK / Réparation / En panne). */
function etat_chariot_label(string $etat): string
{
    return [
        'disponible'  => 'Opérationnel',
        'maintenance' => 'Réparation',
        'panne'       => 'En panne',
    ][$etat] ?? $etat;
}

/** Libellés lisibles pour les états des palettes. */
function etat_palette_label(string $etat): string
{
    return [
        'conforme'     => 'Conforme',
        'non_conforme' => 'Non conforme',
        'cassee'       => 'Cassée',
    ][$etat] ?? $etat;
}

/** Classe CSS de badge selon l'état du chariot. */
function etat_chariot_badge(string $etat): string
{
    return [
        'disponible'  => 'badge-ok',
        'maintenance' => 'badge-warn',
        'panne'       => 'badge-danger',
    ][$etat] ?? 'badge';
}

/** Classe CSS de badge selon l'état de la palette. */
function etat_palette_badge(string $etat): string
{
    return [
        'conforme'     => 'badge-ok',
        'non_conforme' => 'badge-warn',
        'cassee'       => 'badge-danger',
    ][$etat] ?? 'badge';
}

/** Calcule un pourcentage arrondi (0 si dénominateur nul). */
function pct(float $num, float $den, int $decimales = 1): float
{
    return $den > 0 ? round($num / $den * 100, $decimales) : 0.0;
}

/** Liste ordonnée des unités [clé => libellé]. */
function unites(): array
{
    return [
        'liquide'    => 'Liquide',
        'sachet'     => 'Sachet',
        'transfert'  => 'Transfert',
        'mp'         => 'MP',
        'chargement' => 'Chargement',
        'papier'     => 'Papier',
        'retour'     => 'Retour',
        'dechet'     => 'Déchet',
    ];
}

/** Libellé lisible d'une unité. */
function unite_label(?string $u): string
{
    return $u !== null ? (unites()[$u] ?? $u) : '—';
}

/**
 * Répartition des chariots par unité.
 * Retourne [ 'data' => [clé => nombre] (les 8 unités, zéros inclus),
 *            'total' => chariots affectés à une unité ].
 */
function chariots_par_unite(PDO $pdo): array
{
    $data = array_fill_keys(array_keys(unites()), 0);
    foreach ($pdo->query("SELECT unite, COUNT(*) AS n FROM chariots WHERE unite IS NOT NULL GROUP BY unite") as $row) {
        if (isset($data[$row['unite']])) {
            $data[$row['unite']] = (int) $row['n'];
        }
    }
    return ['data' => $data, 'total' => array_sum($data)];
}

/**
 * Statistiques des chariots : totaux par type, par état et taux de disponibilité.
 */
function stats_chariots(PDO $pdo): array
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM chariots')->fetchColumn();

    $parType = ['electrique' => 0, 'diesel' => 0];
    foreach ($pdo->query('SELECT type, COUNT(*) AS n FROM chariots GROUP BY type') as $row) {
        $parType[$row['type']] = (int) $row['n'];
    }

    $parEtat = ['disponible' => 0, 'maintenance' => 0, 'panne' => 0];
    foreach ($pdo->query('SELECT etat, COUNT(*) AS n FROM chariots GROUP BY etat') as $row) {
        $parEtat[$row['etat']] = (int) $row['n'];
    }

    $taux = $total > 0 ? round($parEtat['disponible'] / $total * 100, 1) : 0.0;

    return [
        'total'        => $total,
        'electrique'   => $parType['electrique'],
        'diesel'       => $parType['diesel'],
        'disponible'   => $parEtat['disponible'],
        'maintenance'  => $parEtat['maintenance'],
        'panne'        => $parEtat['panne'],
        'taux_dispo'   => $taux,
    ];
}

/**
 * Statistiques des palettes.
 * Chaque ligne de palette représente un lot : les compteurs par état additionnent
 * les quantités (SUM), pas le nombre de lignes.
 * Retourne les quantités par état, la quantité totale et le nombre de lots.
 */
function stats_palettes(PDO $pdo): array
{
    $parEtat = ['conforme' => 0, 'non_conforme' => 0, 'cassee' => 0];
    foreach ($pdo->query('SELECT etat, COALESCE(SUM(quantite),0) AS q FROM palettes GROUP BY etat') as $row) {
        $parEtat[$row['etat']] = (int) $row['q'];
    }
    $parEtat['total'] = $parEtat['conforme'] + $parEtat['non_conforme'] + $parEtat['cassee'];
    $parEtat['lots']  = (int) $pdo->query('SELECT COUNT(*) FROM palettes')->fetchColumn();

    return $parEtat;
}

/**
 * Construit la liste ordonnée des périodes [clé => libellé] pour une granularité.
 * $periode : 'jour' (14 derniers jours) | 'mois' (12 derniers mois) | 'annee' (6 dernières années).
 * La clé correspond au regroupement SQL (Y-m-d, Y-m ou Y).
 */
function periodes_labels(string $periode): array
{
    $out = [];
    if ($periode === 'annee') {
        for ($i = 5; $i >= 0; $i--) {
            $y = date('Y', strtotime("-$i year"));
            $out[$y] = $y;
        }
    } elseif ($periode === 'mois') {
        for ($i = 11; $i >= 0; $i--) {
            $k = date('Y-m', strtotime("first day of -$i month"));
            $out[$k] = date('m/Y', strtotime($k . '-01'));
        }
    } else { // jour
        for ($i = 13; $i >= 0; $i--) {
            $k = date('Y-m-d', strtotime("-$i day"));
            $out[$k] = date('d/m', strtotime($k));
        }
    }
    return $out;
}

/**
 * Évolution d'une valeur agrégée dans le temps, selon une granularité.
 * Retourne un tableau [ libellé => valeur ] complété (zéros inclus).
 * Les paramètres $table, $dateCol et $valueExpr sont des constantes internes (pas d'entrée utilisateur).
 */
function evolution(PDO $pdo, string $table, string $dateCol, string $valueExpr, string $periode): array
{
    if ($periode === 'annee') {
        $grp = "YEAR($dateCol)";
    } elseif ($periode === 'mois') {
        $grp = "DATE_FORMAT($dateCol, '%Y-%m')";
    } else {
        $grp = "DATE($dateCol)";
    }

    $map = [];
    $sql = "SELECT $grp AS k, $valueExpr AS v FROM $table WHERE $dateCol IS NOT NULL GROUP BY k";
    foreach ($pdo->query($sql) as $row) {
        $map[(string) $row['k']] = (float) $row['v'];
    }

    $serie = [];
    foreach (periodes_labels($periode) as $key => $label) {
        $serie[$label] = $map[(string) $key] ?? 0;
    }
    return $serie;
}

/**
 * Nombre (quantité) de palettes par état et par période — pour un histogramme empilé.
 * Retourne [ 'labels' => [...], 'conforme' => [...], 'non_conforme' => [...], 'cassee' => [...] ].
 */
function palettes_etat_evolution(PDO $pdo, string $periode): array
{
    if ($periode === 'annee') {
        $grp = "YEAR(created_at)";
    } elseif ($periode === 'mois') {
        $grp = "DATE_FORMAT(created_at, '%Y-%m')";
    } else {
        $grp = "DATE(created_at)";
    }

    // map[cléPériode][etat] = quantité
    $map = [];
    $sql = "SELECT $grp AS k, etat, COALESCE(SUM(quantite),0) AS q
              FROM palettes WHERE created_at IS NOT NULL GROUP BY k, etat";
    foreach ($pdo->query($sql) as $row) {
        $map[(string) $row['k']][$row['etat']] = (int) $row['q'];
    }

    $out = ['labels' => [], 'conforme' => [], 'non_conforme' => [], 'cassee' => []];
    foreach (periodes_labels($periode) as $key => $label) {
        $out['labels'][]       = $label;
        $out['conforme'][]     = $map[(string) $key]['conforme']     ?? 0;
        $out['non_conforme'][] = $map[(string) $key]['non_conforme'] ?? 0;
        $out['cassee'][]       = $map[(string) $key]['cassee']       ?? 0;
    }
    return $out;
}

/** Nombre de chariots mis en service par période (jour / mois / année). */
function chariots_evolution(PDO $pdo, string $periode): array
{
    return evolution($pdo, 'chariots', 'date_mise_service', 'COUNT(*)', $periode);
}

/** Normalise le paramètre de granularité de période. */
function periode_valide(?string $p): string
{
    return in_array($p, ['jour', 'mois', 'annee'], true) ? $p : 'jour';
}

/**
 * Rend les onglets Jour / Mois / Année en conservant les autres paramètres GET.
 */
function periode_selector(string $active): string
{
    $labels = ['jour' => 'Jour', 'mois' => 'Mois', 'annee' => 'Année'];
    $html = '<div class="periode-tabs">';
    foreach ($labels as $key => $lab) {
        $url = '?' . http_build_query(array_merge($_GET, ['periode' => $key]));
        $cls = $active === $key ? ' class="active"' : '';
        $html .= '<a href="' . htmlspecialchars($url) . '"' . $cls . '>' . $lab . '</a>';
    }
    return $html . '</div>';
}

/**
 * Temps d'arrêt (heures) par chariot, calculé à partir de l'historique.
 * On additionne les durées passées dans un état 'panne' ou 'maintenance'
 * jusqu'au retour à 'disponible' (ou jusqu'à maintenant si toujours arrêté).
 */
function temps_arret_par_chariot(PDO $pdo): array
{
    $chariots = $pdo->query('SELECT id, etat FROM chariots ORDER BY id')->fetchAll();

    $histStmt = $pdo->query(
        'SELECT chariot_id, nouvel_etat, date_evenement
           FROM chariot_historique
       ORDER BY chariot_id, date_evenement'
    );
    $histoParChariot = [];
    foreach ($histStmt as $row) {
        $histoParChariot[$row['chariot_id']][] = $row;
    }

    $result = [];
    foreach ($chariots as $ch) {
        $secondes = 0;
        $debutArret = null;
        foreach ($histoParChariot[$ch['id']] ?? [] as $evt) {
            $ts = strtotime($evt['date_evenement']);
            if (in_array($evt['nouvel_etat'], ['panne', 'maintenance'], true)) {
                if ($debutArret === null) {
                    $debutArret = $ts;
                }
            } elseif ($evt['nouvel_etat'] === 'disponible' && $debutArret !== null) {
                $secondes += $ts - $debutArret;
                $debutArret = null;
            }
        }
        // Arrêt toujours en cours.
        if ($debutArret !== null) {
            $secondes += time() - $debutArret;
        }

        $result[] = [
            'ref'    => '#' . $ch['id'],
            'etat'   => $ch['etat'],
            'heures' => round($secondes / 3600, 1),
        ];
    }

    return $result;
}

/**
 * Prépare l'intervalle [debut, fin[ et le libellé d'une période de rapport.
 * $type : 'journalier' | 'mensuel' | 'annuel'
 * $ref  : référence saisie (AAAA-MM-JJ, AAAA-MM ou AAAA selon le type).
 */
function rapport_intervalle(string $type, string $ref): array
{
    switch ($type) {
        case 'mensuel':
            $ref = preg_match('/^\d{4}-\d{2}$/', $ref) ? $ref : date('Y-m');
            $debut = $ref . '-01';
            $fin = date('Y-m-d', strtotime($debut . ' +1 month'));
            $libelle = 'Rapport mensuel — ' . date('m/Y', strtotime($debut));
            break;
        case 'annuel':
            $ref = preg_match('/^\d{4}$/', $ref) ? $ref : date('Y');
            $debut = $ref . '-01-01';
            $fin = ($ref + 1) . '-01-01';
            $libelle = 'Rapport annuel — ' . $ref;
            break;
        case 'journalier':
        default:
            $ref = preg_match('/^\d{4}-\d{2}-\d{2}$/', $ref) ? $ref : date('Y-m-d');
            $debut = $ref;
            $fin = date('Y-m-d', strtotime($debut . ' +1 day'));
            $libelle = 'Rapport journalier — ' . date('d/m/Y', strtotime($debut));
            break;
    }

    return ['debut' => $debut, 'fin' => $fin, 'libelle' => $libelle];
}

/**
 * Agrège les données d'un rapport pour une période donnée.
 */
function rapport_data(PDO $pdo, string $type, string $ref): array
{
    $int = rapport_intervalle($type, $ref);
    $debut = $int['debut'];
    $fin = $int['fin'];

    // Changements d'état des chariots dans la période.
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM chariot_historique WHERE date_evenement >= ? AND date_evenement < ?'
    );
    $stmt->execute([$debut, $fin]);
    $changementsEtat = (int) $stmt->fetchColumn();

    // Nouvelles palettes / chariots créés dans la période.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM palettes WHERE created_at >= ? AND created_at < ?');
    $stmt->execute([$debut, $fin]);
    $nouvellesPalettes = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM chariots WHERE created_at >= ? AND created_at < ?');
    $stmt->execute([$debut, $fin]);
    $nouveauxChariots = (int) $stmt->fetchColumn();

    return [
        'type'               => $type,
        'ref'                => $ref,
        'libelle'            => $int['libelle'],
        'debut'              => $debut,
        'fin'                => $fin,
        'changements_etat'   => $changementsEtat,
        'nouvelles_palettes' => $nouvellesPalettes,
        'nouveaux_chariots'  => $nouveauxChariots,
        'chariots'           => stats_chariots($pdo),
        'palettes'           => stats_palettes($pdo),
    ];
}
