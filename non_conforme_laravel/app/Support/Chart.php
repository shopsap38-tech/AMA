<?php

namespace App\Support;

/**
 * Générateur de graphiques SVG (sans dépendance externe, rendu côté serveur).
 */
class Chart
{
    /** Palette catégorielle cohérente avec le thème. */
    public static function palette(): array
    {
        return ['#b02a37', '#d98a3d', '#2f7d95', '#5b8c3e', '#7a5ba6', '#c9a227'];
    }

    /**
     * Grand graphique en barres, style professionnel :
     * dégradés, coins arrondis, ombre portée, grille légère et axe des valeurs.
     *
     * @param array<string,float|int> $data
     */
    public static function barChartPro(array $data, string $unit = '', ?array $colors = null): string
    {
        if (empty($data)) {
            return '<p class="chart-empty">Aucune donnée.</p>';
        }
        $colors = $colors ?: self::palette();

        $w = 900; $h = 460;
        $padL = 64; $padR = 32; $padT = 40; $padB = 70;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;

        $max = max($data);
        $max = $max > 0 ? $max : 1;
        $step = pow(10, floor(log10($max)));
        $niceMax = ceil($max / $step) * $step;
        if ($niceMax == $max) {
            $niceMax += $step;
        }

        $n = count($data);
        $slot = $plotW / $n;
        $barW = min(120, $slot * 0.5);

        $defs = '<defs>';
        for ($k = 0; $k < count($colors); $k++) {
            $c = $colors[$k];
            $light = self::lighten($c, 0.22);
            $defs .= '<linearGradient id="ncgrad' . $k . '" x1="0" y1="0" x2="0" y2="1">'
                   . '<stop offset="0%" stop-color="' . $light . '"/>'
                   . '<stop offset="100%" stop-color="' . $c . '"/></linearGradient>';
        }
        $defs .= '<filter id="ncshadow" x="-20%" y="-20%" width="140%" height="140%">'
               . '<feDropShadow dx="0" dy="4" stdDeviation="5" flood-color="#000" flood-opacity="0.18"/></filter>';
        $defs .= '</defs>';

        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="chart-svg chart-pro" role="img">' . $defs;

        for ($i = 0; $i <= 5; $i++) {
            $y = $padT + $plotH - ($plotH * $i / 5);
            $val = $niceMax * $i / 5;
            $svg .= '<line x1="' . $padL . '" y1="' . round($y, 1) . '" x2="' . ($w - $padR) . '" y2="' . round($y, 1) . '" class="chart-grid"/>';
            $svg .= '<text x="' . ($padL - 12) . '" y="' . round($y + 4, 1) . '" class="chart-axis-pro" text-anchor="end">' . self::fmt($val) . '</text>';
        }

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
                  . '<title>' . e($label . ' : ' . self::fmt($value) . ' ' . $unit) . '</title></rect>';
            $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . round($y - 14, 1) . '" class="chart-value-pro" text-anchor="middle">' . self::fmt($value) . '</text>';
            $svg .= '<text x="' . round($x + $barW / 2, 1) . '" y="' . ($baseY + 30) . '" class="chart-label-pro" text-anchor="middle">' . e($label) . '</text>';
            $svg .= '<circle cx="' . round($x + $barW / 2, 1) . '" cy="' . ($baseY + 44) . '" r="5" fill="' . $color . '"/>';
            $i++;
        }
        $svg .= '</svg>';

        return $svg;
    }

    /** Formatage numérique à la française. */
    public static function fmt(float|int $v): string
    {
        $v = (float) $v;
        if (floor($v) == $v) {
            return number_format($v, 0, ',', ' ');
        }
        return number_format($v, 2, ',', ' ');
    }

    /** Éclaircit une couleur hexadécimale d'un facteur (0..1). */
    private static function lighten(string $hex, float $factor): string
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
}
