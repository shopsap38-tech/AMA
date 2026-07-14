<?php

namespace App\Http\Controllers;

use App\Models\NcSemiFini;
use Illuminate\Http\Request;

class SemiFiniController extends Controller
{
    public function index()
    {
        $rows = NcSemiFini::orderByDesc('nbr_palettes')->orderBy('description_article')->get();

        $totaux = [
            'quantite' => (int) $rows->sum('quantite'),
            'palettes' => (int) $rows->sum('nbr_palettes'),
            'poids_kg' => (float) $rows->sum('poids_total_kg'),
        ];

        return view('semi_fini.index', compact('rows', 'totaux'));
    }

    public function create()
    {
        return view('semi_fini.form', ['item' => new NcSemiFini(['motif' => 'colmaté', 'decision_sq' => 'a recycler'])]);
    }

    public function store(Request $request)
    {
        NcSemiFini::create($this->validated($request));

        return redirect()->route('semi-fini.index')->with('success', 'Non-conformité ajoutée.');
    }

    public function edit(NcSemiFini $semi_fini)
    {
        return view('semi_fini.form', ['item' => $semi_fini]);
    }

    public function update(Request $request, NcSemiFini $semi_fini)
    {
        $semi_fini->update($this->validated($request));

        return redirect()->route('semi-fini.index')->with('success', 'Non-conformité mise à jour.');
    }

    public function destroy(NcSemiFini $semi_fini)
    {
        $semi_fini->delete();

        return redirect()->route('semi-fini.index')->with('success', 'Non-conformité supprimée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'numero_article'      => ['required', 'string', 'max:30'],
            'description_article' => ['required', 'string', 'max:255'],
            'quantite'            => ['required', 'integer', 'min:0'],
            'nbr_palettes'        => ['required', 'integer', 'min:0'],
            'poids_total_kg'      => ['required', 'numeric', 'min:0'],
            'motif'               => ['nullable', 'string', 'max:150'],
            'decision_sq'         => ['nullable', 'string', 'max:150'],
        ]);
    }
}
