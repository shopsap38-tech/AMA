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
 * Grand graphique en barres, style professionnel :
 * dégradés, coins arrondis, ombre portée, grille légère et axe des valeurs.
 * @param array $data  ['Libellé' => valeur, ...]
 */
function svg_bar_chart_pro(array $data, string $unit = '', ?array $colors = null): string
{
    if (empty($data)) {
        return '<p class="chart-empty">Aucune donnée.</p>';
    }
    $colors = $colors ?: nc_palette();

    $w = 900; $h = 460;
    $padL = 64; $padR = 32; $padT = 40; $padB = 70;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;

    $max = max($data);
    $max = $max > 0 ? $max : 1;
    // arrondi « joli » du maximum de l'axe
    $step = pow(10, floor(log10($max)));
    $niceMax = ceil($max / $step) * $step;
    if ($niceMax == $max) { $niceMax += $step; }

    $n = count($data);
    $slot = $plotW / $n;
    $barW = min(120, $slot * 0.5);

    // dégradés + ombre
    $defs = '<defs>';
    for ($k = 0; $k < count($colors); $k++) {
        $c = $colors[$k];
        $light = nc_lighten($c, 0.22);
        $defs .= '<linearGradient id="ncgrad' . $k . '" x1="0" y1="0" x2="0" y2="1">'
               . '<stop offset="0%" stop-color="' . $light . '"/>'
               . '<stop offset="100%" stop-color="' . $c . '"/></linearGradient>';
    }
    $defs .= '<filter id="ncshadow" x="-20%" y="-20%" width="140%" height="140%">'
           . '<feDropShadow dx="0" dy="4" stdDeviation="5" flood-color="#000" flood-opacity="0.18"/></filter>';
    $defs .= '</defs>';

    $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="chart-svg chart-pro" role="img">' . $defs;

    // grille horizontale + axe Y (5 niveaux)
    for ($i = 0; $i <= 5; $i++) {
        $y = $padT + $plotH - ($plotH * $i / 5);
        $val = $niceMax * $i / 5;
        $svg .= '<line x1="' . $padL . '" y1="' . round($y, 1) . '" x2="' . ($w - $padR) . '" y2="' . round($y, 1) . '" class="chart-grid"/>';
        $svg .= '<text x="' . ($padL - 12) . '" y="' . round($y + 4, 1) . '" class="chart-axis-pro" text-anchor="end">' . nc_fmt($val) . '</text>';
    }
    // ligne de base
    $baseY = $padT + $plotH;
    $svg .= '<line x1="' . $padL . '" y1="' . $baseY . '" x2="' . ($w - $padR) . '" y2="' . $baseY . '" class="chart-baseline"/>';

    $i = 0;
    foreach ($data as $label => $value) {
        $bh = $plotH * ($value / $niceMax);
        $x = $padL + $slot * $i + ($slot - $barW) / 2;
        $y = $baseY - $bh;
        $color = $colors[$i % count($colors)];
        $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($barW, 1) . '" height="' . round($bh, 1) . '"'
              . ' rx="8" fill="url(#ncgrad' . ($i % count($colors)) . ')" filter="url(#ncshadow)">'
              . '<title>' . htmlspecialchars($label . ' : ' . nc_fmt($value) . ' ' . $unit) . '</title></rect>';
        // pastille + valeur au-dessus
        $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . round($y - 14, 1) . '" class="chart-value-pro" text-anchor="middle">' . nc_fmt($value) . '</text>';
        // libellé de catégorie
        $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . ($baseY + 30) . '" class="chart-label-pro" text-anchor="middle">' . htmlspecialchars($label) . '</text>';
        // point de couleur sous le libellé
        $svg .= '<circle cx="' . round($x + $barW / 2, 1) . '" cy="' . ($baseY + 44) . '" r="5" fill="' . $color . '"/>';
        $i++;
    }
    $svg .= '</svg>';
    return $svg;
}

/** Éclaircit une couleur hexadécimale d'un facteur (0..1). */
function nc_lighten(string $hex, float $factor): string
{
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = (int) round($r + (255 - $r) * $factor);
    $g = (int) round($g + (255 - $g) * $factor);
    $b = (int) round($b + (255 - $b) * $factor);
    return sprintf('#%02x%02x%02x', $r, $g, $b);
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
