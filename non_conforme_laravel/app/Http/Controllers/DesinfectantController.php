<?php

namespace App\Http\Controllers;

use App\Models\NcDesinfectant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesinfectantController extends Controller
{
    public function index()
    {
        $rows = NcDesinfectant::orderByDesc('stock_mag')->get();

        $totaux = [
            'palettes' => (int) $rows->sum('nbr_palettes'),
            'valeur'   => (float) NcDesinfectant::sum(DB::raw('stock_mag * prix_unitaire')),
            'poids_kg' => (float) NcDesinfectant::sum(DB::raw('stock_mag * poids_par_carton_kg')),
        ];

        return view('desinfectant.index', compact('rows', 'totaux'));
    }

    public function create()
    {
        return view('desinfectant.form', ['item' => new NcDesinfectant([
            'motif' => 'produit expiré', 'responsable' => 'Service qualité',
        ])]);
    }

    public function store(Request $request)
    {
        NcDesinfectant::create($this->validated($request));

        return redirect()->route('desinfectant.index')->with('success', 'Non-conformité ajoutée.');
    }

    public function edit(NcDesinfectant $desinfectant)
    {
        return view('desinfectant.form', ['item' => $desinfectant]);
    }

    public function update(Request $request, NcDesinfectant $desinfectant)
    {
        $desinfectant->update($this->validated($request));

        return redirect()->route('desinfectant.index')->with('success', 'Non-conformité mise à jour.');
    }

    public function destroy(NcDesinfectant $desinfectant)
    {
        $desinfectant->delete();

        return redirect()->route('desinfectant.index')->with('success', 'Non-conformité supprimée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type_article'        => ['nullable', 'string', 'max:50'],
            'numero_article'      => ['required', 'string', 'max:30'],
            'description_article' => ['required', 'string', 'max:255'],
            'stock_mag'           => ['required', 'integer', 'min:0'],
            'date_expiration'     => ['nullable', 'date'],
            'code_um'             => ['nullable', 'string', 'max:50'],
            'nbr_palettes'        => ['required', 'integer', 'min:0'],
            'prix_unitaire'       => ['nullable', 'numeric', 'min:0'],
            'poids_par_carton_kg' => ['nullable', 'numeric', 'min:0'],
            'date_blocage'        => ['nullable', 'date'],
            'motif'               => ['nullable', 'string', 'max:150'],
            'responsable'         => ['nullable', 'string', 'max:150'],
            'decision_cq'         => ['nullable', 'string', 'max:150'],
        ]);
    }
}
