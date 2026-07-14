<?php

namespace App\Http\Controllers;

use App\Models\NcBigBag;
use App\Models\NcDesinfectant;
use App\Models\NcSemiFini;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $sf = [
            'lignes'   => NcSemiFini::count(),
            'quantite' => (int) NcSemiFini::sum('quantite'),
            'palettes' => (int) NcSemiFini::sum('nbr_palettes'),
            'poids_kg' => (float) NcSemiFini::sum('poids_total_kg'),
        ];

        $de = [
            'lignes'       => NcDesinfectant::count(),
            'palettes'     => (int) NcDesinfectant::sum('nbr_palettes'),
            'total_valeur' => (float) NcDesinfectant::sum(DB::raw('stock_mag * prix_unitaire')),
            'poids_kg'     => (float) NcDesinfectant::sum(DB::raw('stock_mag * poids_par_carton_kg')),
        ];

        $bb = [
            'lignes'   => NcBigBag::count(),
            'big_bags' => (int) NcBigBag::sum('total_big_bag'),
            'tonnage'  => (float) NcBigBag::sum('tonnage'),
        ];

        $chartPalettes = [
            'Semi-fini'    => $sf['palettes'],
            'Désinfectant' => $de['palettes'],
            'Big Bag'      => $bb['big_bags'],
        ];

        return view('dashboard', compact('sf', 'de', 'bb', 'chartPalettes'));
    }
}
