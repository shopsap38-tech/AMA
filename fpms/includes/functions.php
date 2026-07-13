<?php

/**
 * Fonctions utilitaires et requêtes statistiques partagées par les pages.
 */

/** Libellés lisibles pour les états des chariots. */
function etat_chariot_label(string $etat): string
{
    return [
        'disponible'  => 'Disponible',
        'maintenance' => 'En maintenance',
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
 * Statistiques des palettes : totaux par état.
 */
function stats_palettes(PDO $pdo): array
{
    $parEtat = ['conforme' => 0, 'non_conforme' => 0, 'cassee' => 0];
    foreach ($pdo->query('SELECT etat, COUNT(*) AS n FROM palettes GROUP BY etat') as $row) {
        $parEtat[$row['etat']] = (int) $row['n'];
    }
    $parEtat['total'] = array_sum($parEtat);

    return $parEtat;
}

/**
 * Nombre de réparations par jour sur les N derniers jours.
 * Retourne un tableau [ 'AAAA-MM-JJ' => nombre ] ordonné du plus ancien au plus récent.
 */
function reparations_par_jour(PDO $pdo, int $jours = 14): array
{
    $stmt = $pdo->prepare(
        'SELECT date_reparation, COUNT(*) AS n
           FROM reparations
          WHERE date_reparation >= CURDATE() - INTERVAL :jours DAY
       GROUP BY date_reparation'
    );
    $stmt->bindValue(':jours', $jours, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    foreach ($stmt as $row) {
        $data[$row['date_reparation']] = (int) $row['n'];
    }

    // Compléter tous les jours de l'intervalle, même à zéro.
    $serie = [];
    for ($i = $jours - 1; $i >= 0; $i--) {
        $jour = date('Y-m-d', strtotime("-$i day"));
        $serie[$jour] = $data[$jour] ?? 0;
    }

    return $serie;
}

/**
 * Temps d'arrêt (heures) par chariot, calculé à partir de l'historique.
 * On additionne les durées passées dans un état 'panne' ou 'maintenance'
 * jusqu'au retour à 'disponible' (ou jusqu'à maintenant si toujours arrêté).
 */
function temps_arret_par_chariot(PDO $pdo): array
{
    $chariots = $pdo->query('SELECT id, code, etat FROM chariots ORDER BY code')->fetchAll();

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
            'code'   => $ch['code'],
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

    // Réparations de la période, par résultat.
    $stmt = $pdo->prepare(
        'SELECT resultat, COUNT(*) AS n
           FROM reparations
          WHERE date_reparation >= ? AND date_reparation < ?
       GROUP BY resultat'
    );
    $stmt->execute([$debut, $fin]);
    $rep = ['reparee' => 0, 'irreparable' => 0];
    foreach ($stmt as $row) {
        $rep[$row['resultat']] = (int) $row['n'];
    }

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
        'reparations_total'  => $rep['reparee'] + $rep['irreparable'],
        'reparations_reparee'=> $rep['reparee'],
        'reparations_irrep'  => $rep['irreparable'],
        'changements_etat'   => $changementsEtat,
        'nouvelles_palettes' => $nouvellesPalettes,
        'nouveaux_chariots'  => $nouveauxChariots,
        'chariots'           => stats_chariots($pdo),
        'palettes'           => stats_palettes($pdo),
    ];
}
