<?php
/**
 * Structure de l'entrepôt (zones + racks) et répartition automatique des
 * palettes selon le taux d'occupation fourni par SQL Server.
 *
 * Utilisé par warehouse_api.php (API JSON) et entrepot3d.php (visualisation).
 */

/**
 * Définition des zones : nombre de racks et capacité totale (en palettes).
 * Total : 343 racks, 11 544 emplacements palette.
 */
const ZONES = [
    'A' => ['racks' => 68, 'capacite' => 1352],
    'B' => ['racks' => 38, 'capacite' => 1820],
    'C' => ['racks' => 36, 'capacite' => 1716],
    'D' => ['racks' => 4,  'capacite' => 48],
    'E' => ['racks' => 33, 'capacite' => 844],
    'F' => ['racks' => 44, 'capacite' => 1748],
    'G' => ['racks' => 82, 'capacite' => 2648],
    'H' => ['racks' => 38, 'capacite' => 1368],
];

/** Répartit un entier en $n parts entières aussi égales que possible. */
function repartir_entier(int $total, int $n): array
{
    if ($n <= 0) {
        return [];
    }
    $base = intdiv($total, $n);
    $rem  = $total - $base * $n;
    $out  = array_fill(0, $n, $base);
    for ($i = 0; $i < $rem; $i++) {
        $out[$i]++;
    }
    return $out;
}

/** Partie fractionnaire (0..1) — pour une pseudo-aléa déterministe. */
function _frac(float $x): float
{
    return $x - floor($x);
}

/**
 * Construit l'entrepôt complet : capacité par rack, palettes réparties, taux,
 * agrégats par zone. La répartition est déterministe (même rendu à chaque appel)
 * avec une variation par rack pour un affichage réaliste (vert/orange/rouge).
 *
 * @param float $occupeesInput Palettes occupées (issu de SQL Server).
 * @return array{
 *   capacite_totale:int, occupees:int, libres:int, taux:float,
 *   nb_racks:int,
 *   zones:array<int,array{zone:string,racks:int,capacity:int,occupied:int,free:int,rate:float}>,
 *   racks:array<int,array{code:string,zone:string,capacity:int,occupied:int,free:int,rate:float}>
 * }
 */
function construire_entrepot(float $occupeesInput): array
{
    $capaciteTotale = 0;
    foreach (ZONES as $z) {
        $capaciteTotale += $z['capacite'];
    }

    $occupeesTotal = (int) round($occupeesInput);
    $occupeesTotal = max(0, min($occupeesTotal, $capaciteTotale));
    $rateGlobal    = $capaciteTotale > 0 ? $occupeesTotal / $capaciteTotale : 0.0;

    // 1) Capacité par rack + occupation « brute » avec variation déterministe.
    $racks     = [];
    $globalIdx = 0;
    foreach (ZONES as $zoneCode => $z) {
        $caps = repartir_entier($z['capacite'], $z['racks']);
        for ($i = 0; $i < $z['racks']; $i++) {
            $cap    = $caps[$i];
            $r      = _frac(sin(($globalIdx + 1) * 12.9898) * 43758.5453);
            $factor = 0.35 + 0.95 * $r; // variation dans [0.35 ; 1.30]
            $raw    = min($cap, $rateGlobal * $cap * $factor);
            $racks[] = [
                'code'     => $zoneCode . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'zone'     => $zoneCode,
                'capacity' => $cap,
                'occupied' => 0,
                'raw'      => $raw,
            ];
            $globalIdx++;
        }
    }

    // 2) Normalisation : la somme des occupied doit égaler occupeesTotal.
    $sumRaw = array_sum(array_column($racks, 'raw'));
    if ($sumRaw > 0) {
        foreach ($racks as &$rk) {
            $val = (int) round($rk['raw'] * $occupeesTotal / $sumRaw);
            $rk['occupied'] = max(0, min($rk['capacity'], $val));
        }
        unset($rk);
    }

    // 3) Correction du petit écart d'arrondi.
    $diff  = $occupeesTotal - array_sum(array_column($racks, 'occupied'));
    $n     = count($racks);
    $ri    = 0;
    $guard = 0;
    while ($diff !== 0 && $n > 0 && $guard < 2000000) {
        $rk = &$racks[$ri % $n];
        if ($diff > 0 && $rk['occupied'] < $rk['capacity']) {
            $rk['occupied']++;
            $diff--;
        } elseif ($diff < 0 && $rk['occupied'] > 0) {
            $rk['occupied']--;
            $diff++;
        }
        unset($rk);
        $ri++;
        $guard++;
    }

    // 4) Finalisation (free, rate) + agrégats par zone.
    $zoneAgg = [];
    foreach ($racks as &$rk) {
        unset($rk['raw']);
        $rk['free'] = $rk['capacity'] - $rk['occupied'];
        $rk['rate'] = $rk['capacity'] > 0 ? round($rk['occupied'] / $rk['capacity'], 4) : 0.0;
        $z = $rk['zone'];
        if (!isset($zoneAgg[$z])) {
            $zoneAgg[$z] = ['zone' => $z, 'racks' => 0, 'capacity' => 0, 'occupied' => 0];
        }
        $zoneAgg[$z]['racks']++;
        $zoneAgg[$z]['capacity'] += $rk['capacity'];
        $zoneAgg[$z]['occupied'] += $rk['occupied'];
    }
    unset($rk);
    foreach ($zoneAgg as &$za) {
        $za['free'] = $za['capacity'] - $za['occupied'];
        $za['rate'] = $za['capacity'] > 0 ? round($za['occupied'] / $za['capacity'] * 100, 1) : 0.0;
    }
    unset($za);

    return [
        'capacite_totale' => $capaciteTotale,
        'occupees'        => $occupeesTotal,
        'libres'          => $capaciteTotale - $occupeesTotal,
        'taux'            => round($rateGlobal * 100, 1),
        'nb_racks'        => $n,
        'zones'           => array_values($zoneAgg),
        'racks'           => $racks,
    ];
}
