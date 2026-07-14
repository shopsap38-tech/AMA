<?php

namespace Database\Seeders;

use App\Models\NcBigBag;
use App\Models\NcDesinfectant;
use App\Models\NcSemiFini;
use Illuminate\Database\Seeder;

/**
 * Données initiales reprises du fichier NON_CONFORME.xlsx (au 2026-07-14).
 */
class NonConformeSeeder extends Seeder
{
    public function run(): void
    {
        $semiFini = [
            ['80010073', 'BAÑO MATIC Océanique Sac 9 Kgs',    480, 8, 4320],
            ['80010002', 'MIO MATIC 3*5Kg Sachet',            240, 6, 1200],
            ['80010054', 'ZEN MATIC S.M 750 Grs',             200, 4, 2400],
            ['80010059', 'A+ MATIC Océanique Sac 9 Kgs',      210, 3, 1890],
            ['80000038', 'MIO Semi-auto 1 Kg Rose - Sac 10P', 150, 3, 1500],
        ];
        foreach ($semiFini as $r) {
            NcSemiFini::create([
                'numero_article'      => $r[0],
                'description_article' => $r[1],
                'quantite'            => $r[2],
                'nbr_palettes'        => $r[3],
                'poids_total_kg'      => $r[4],
                'motif'               => 'colmaté',
                'decision_sq'         => 'a recycler',
            ]);
        }

        // type, num, description, stock, expiration, code_um, palettes, prix, poids/carton
        $desinfectant = [
            ['SOLUTION H.A', '80110004', 'MIO SOLUTION H.A DESINFECTANT 100ml / ETHANOL',        1984, null,        'Carton 24', 4, 131.9200,  2.8500],
            ['ALCOOL',       '80110018', 'Pack MIO AL70% 50ml MIO X 2 + MIO AL70% 50ml GRATUIT', 3294, null,        'Unité',     3,  16.9167,  0.1820],
            ['SOLUTION H.A', '80110006', 'MIO SOLUTION H.A DESINFECTANT 5L',                      218,  null,        'Carton 3',  4, 339.5000, 14.6650],
            ['SOLUTION H.A', '80110007', 'MIO SOLUTION H.A DESINFECTANT 500ml',                   169,  '2025-08-05','Carton 9',  2, 135.3150,  4.4463],
            ['ALCOOL',       '80110009', 'MIO ALCOOL 70° DESINFECTANT SPRAY 250ml',               105,  '2026-09-25','Carton 25', 2, 212.1875,  6.3500],
            ['ALCOOL',       '80110010', 'MIO ALCOOL 70° DESINFECTANT SPRAY 50ml',                 40,  null,        'Carton 56', 1, 217.2800,  3.3956],
            ['ALCOOL',       '80110023', 'FAYZ SOLUTION ALCO. DESINFECTANT 250ml',                 38,  null,        'Carton 25', 1, 404.1667,  6.3500],
            ['SOLUTION H.A', '80110008', 'MIO SOLUTION H.A DESINFECTANT 1L',                       37,  null,        'Carton 6',  1, 167.3250,  6.6495],
            ['ALCOOL',       '80110026', 'FAYZ SOLUTION ALCO. DESINFECTANT 100ml',                 36,  null,        'Carton 25', 1, 404.1667,  2.8500],
            ['ALCOOL',       '80110025', 'FAYZ SOLUTION ALCO. DESINFECTANT 1L',                    24,  null,        'Carton 6',  1, 315.2500,  6.6495],
            ['ALCOOL',       '80110024', 'FAYZ SOLUTION ALCO. DESINFECTANT 500ml',                 11,  null,        'Carton 9',  1, 258.2625,  4.4463],
            ['GEL',          '80110019', 'FAYZ GEL MAINS DESINFECTANT 100ml',                      29,  null,        'Carton 24', 1, 223.1000,  2.8500],
        ];
        foreach ($desinfectant as $r) {
            NcDesinfectant::create([
                'type_article'        => $r[0],
                'numero_article'      => $r[1],
                'description_article' => $r[2],
                'stock_mag'           => $r[3],
                'date_expiration'     => $r[4],
                'code_um'             => $r[5],
                'nbr_palettes'        => $r[6],
                'prix_unitaire'       => $r[7],
                'poids_par_carton_kg' => $r[8],
                'motif'               => 'produit expiré',
                'responsable'         => 'Service qualité',
            ]);
        }

        NcBigBag::create([
            'date_nc'       => '2026-07-14',
            'total_big_bag' => 52,
            'tonnage'       => 34840,
        ]);
    }
}
