@extends('layouts.app')
@section('title', 'PF Semi-fini-matic - Non-conformités')

@section('content')
    <div class="toolbar">
        <h2>PF Semi-fini-matic</h2>
        <a class="btn" href="{{ route('semi-fini.create') }}">+ Ajouter une non-conformité</a>
    </div>

    <div class="table-scroll">
    <table class="table table-clean">
        <thead>
            <tr>
                <th>N° article</th>
                <th>Description article</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Nbr palettes</th>
                <th class="text-right">Poids total (kg)</th>
                <th class="text-right">Poids total (T)</th>
                <th>Motif</th>
                <th>Décision SQ</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="nowrap"><strong>{{ $r->numero_article }}</strong></td>
                    <td>{{ $r->description_article }}</td>
                    <td class="text-right num">{{ number_format($r->quantite, 0, ',', ' ') }}</td>
                    <td class="text-right num">{{ $r->nbr_palettes }}</td>
                    <td class="text-right num">{{ number_format($r->poids_total_kg, 2, ',', ' ') }}</td>
                    <td class="text-right num">{{ number_format($r->poids_total_t, 3, ',', ' ') }}</td>
                    <td class="nowrap">@if ($r->motif)<span class="badge badge-warn">{{ $r->motif }}</span>@endif</td>
                    <td>{{ $r->decision_sq }}</td>
                    <td class="actions nowrap">
                        <a href="{{ route('semi-fini.edit', $r) }}">Modifier</a>
                        <form action="{{ route('semi-fini.destroy', $r) }}" method="post" class="inline-form"
                              onsubmit="return confirm('Supprimer cette ligne ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">Aucune non-conformité enregistrée.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="text-right num">{{ number_format($totaux['quantite'], 0, ',', ' ') }}</td>
                <td class="text-right num">{{ $totaux['palettes'] }}</td>
                <td class="text-right num">{{ number_format($totaux['poids_kg'], 2, ',', ' ') }}</td>
                <td class="text-right num">{{ number_format($totaux['poids_kg'] / 1000, 3, ',', ' ') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
    </div>
@endsection
