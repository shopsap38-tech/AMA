<?php
/**
 * Générateur de graphiques SVG (sans dépendance externe, rendu côté serveur).
 * Toutes les valeurs affichées sont échappées.
 */

// Palette catégorielle cohérente avec le thème
function nc_palette(): array
{
    return ['#b02a37', '#d98a3d', '#2f7d95', '#5b8c3e', '#7a5ba6', '#c9a227'];
}

/**
 * Graphique en barres verticales.
 * @param array $data  ['Libellé' => valeur, ...]
 */
function svg_bar_chart(array $data, string $unit = '', ?array $colors = null): string
{
    if (empty($data)) {
        return '<p class="chart-empty">Aucune donnée.</p>';
    }
    $colors = $colors ?: nc_palette();

    $w = 460; $h = 260;
    $padL = 40; $padR = 20; $padT = 20; $padB = 48;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;

    $max = max($data);
    $max = $max > 0 ? $max : 1;
    $n = count($data);
    $slot = $plotW / $n;
    $barW = min(70, $slot * 0.6);

    $svg  = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="chart-svg" role="img">';
    // lignes de grille (4 niveaux)
    for ($i = 0; $i <= 4; $i++) {
        $y = $padT + $plotH - ($plotH * $i / 4);
        $val = $max * $i / 4;
        $svg .= '<line x1="' . $padL . '" y1="' . round($y, 1) . '" x2="' . ($w - $padR) . '" y2="' . round($y, 1) . '" class="chart-grid"/>';
        $svg .= '<text x="' . ($padL - 6) . '" y="' . round($y + 3, 1) . '" class="chart-axis" text-anchor="end">' . nc_fmt($val) . '</text>';
    }

    $i = 0;
    foreach ($data as $label => $value) {
        $bh = $plotH * ($value / $max);
        $x = $padL + $slot * $i + ($slot - $barW) / 2;
        $y = $padT + $plotH - $bh;
        $color = $colors[$i % count($colors)];
        $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($barW, 1) . '" height="' . round($bh, 1) . '" rx="3" fill="' . $color . '"><title>' . htmlspecialchars($label . ' : ' . nc_fmt($value) . ' ' . $unit) . '</title></rect>';
        // valeur au-dessus
        $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . round($y - 6, 1) . '" class="chart-value" text-anchor="middle">' . nc_fmt($value) . '</text>';
        // libellé sous la barre
        $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . ($h - $padB + 16) . '" class="chart-label" text-anchor="middle">' . htmlspecialchars(nc_wrap($label)) . '</text>';
        $i++;
    }
    $svg .= '</svg>';
    return $svg;
}

/**
 * Graphique en anneau (donut) avec légende.
 * @param array $data  ['Libellé' => valeur, ...]
 */
function svg_donut(array $data, string $unit = '', ?array $colors = null): string
{
    $data = array_filter($data, fn($v) => $v > 0);
    if (empty($data)) {
        return '<p class="chart-empty">Aucune donnée.</p>';
    }
    $colors = $colors ?: nc_palette();

    $size = 220; $cx = 110; $cy = 110; $r = 80; $sw = 34;
    $c = 2 * M_PI * $r;
    $total = array_sum($data);

    $svg = '<div class="donut-wrap">';
    $svg .= '<svg viewBox="0 0 ' . $size . ' ' . $size . '" class="chart-svg donut" role="img">';
    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="#eceff1" stroke-width="' . $sw . '"/>';

    $offset = 0.0;
    $i = 0;
    foreach ($data as $label => $value) {
        $frac = $value / $total;
        $len = $c * $frac;
        $color = $colors[$i % count($colors)];
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . $color . '" stroke-width="' . $sw . '"'
              . ' stroke-dasharray="' . round($len, 2) . ' ' . round($c - $len, 2) . '"'
              . ' stroke-dashoffset="' . round(-$offset, 2) . '"'
              . ' transform="rotate(-90 ' . $cx . ' ' . $cy . ')">'
              . '<title>' . htmlspecialchars($label . ' : ' . nc_fmt($value) . ' ' . $unit . ' (' . round($frac * 100) . '%)') . '</title></circle>';
        $offset += $len;
        $i++;
    }
    // total au centre
    $svg .= '<text x="' . $cx . '" y="' . ($cy - 4) . '" class="donut-total" text-anchor="middle">' . nc_fmt($total) . '</text>';
    $svg .= '<text x="' . $cx . '" y="' . ($cy + 14) . '" class="donut-unit" text-anchor="middle">' . htmlspecialchars($unit) . '</text>';
    $svg .= '</svg>';

    // légende
    $svg .= '<ul class="chart-legend">';
    $i = 0;
    foreach ($data as $label => $value) {
        $color = $colors[$i % count($colors)];
        $pct = round($value / $total * 100);
        $svg .= '<li><span class="legend-dot" style="background:' . $color . '"></span>'
              . htmlspecialchars($label) . ' <strong>' . nc_fmt($value) . '</strong> (' . $pct . '%)</li>';
        $i++;
    }
    $svg .= '</ul></div>';
    return $svg;
}

/** Formatage numérique compact à la française. */
function nc_fmt($v): string
{
    $v = (float) $v;
    if (floor($v) == $v) {
        return number_format($v, 0, ',', ' ');
    }
    return number_format($v, 2, ',', ' ');
}

/** Coupe un libellé trop long pour l'axe. */
function nc_wrap(string $label): string
{
    return mb_strlen($label) > 14 ? mb_substr($label, 0, 13) . '…' : $label;
}
