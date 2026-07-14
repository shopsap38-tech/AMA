<?php

namespace App\Http\Controllers;

use App\Models\NcBigBag;
use Illuminate\Http\Request;

class BigBagController extends Controller
{
    public function index()
    {
        $rows = NcBigBag::orderByDesc('date_nc')->get();

        $totaux = [
            'big_bags' => (int) $rows->sum('total_big_bag'),
            'tonnage'  => (float) $rows->sum('tonnage'),
        ];

        return view('big_bag.index', compact('rows', 'totaux'));
    }

    public function create()
    {
        return view('big_bag.form', ['item' => new NcBigBag(['date_nc' => now()->toDateString()])]);
    }

    public function store(Request $request)
    {
        NcBigBag::create($this->validated($request));

        return redirect()->route('big-bag.index')->with('success', 'Non-conformité ajoutée.');
    }

    public function edit(NcBigBag $big_bag)
    {
        return view('big_bag.form', ['item' => $big_bag]);
    }

    public function update(Request $request, NcBigBag $big_bag)
    {
        $big_bag->update($this->validated($request));

        return redirect()->route('big-bag.index')->with('success', 'Non-conformité mise à jour.');
    }

    public function destroy(NcBigBag $big_bag)
    {
        $big_bag->delete();

        return redirect()->route('big-bag.index')->with('success', 'Non-conformité supprimée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'date_nc'       => ['required', 'date'],
            'total_big_bag' => ['required', 'integer', 'min:0'],
            'tonnage'       => ['required', 'numeric', 'min:0'],
        ]);
    }
}
