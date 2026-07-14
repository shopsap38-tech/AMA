@extends('layouts.app')
@section('title', 'Désinfectant - Non-conformités')

@php
    function typeBadge(?string $type): string {
        $t = mb_strtoupper(trim((string) $type));
        if (str_contains($t, 'ALCOOL'))   return 'badge-type-alcool';
        if (str_contains($t, 'SOLUTION')) return 'badge-type-solution';
        if (str_contains($t, 'GEL'))      return 'badge-type-gel';
        return 'badge-type-autre';
    }
@endphp

@section('content')
    <div class="toolbar">
        <h2>Désinfectant</h2>
        <a class="btn" href="{{ route('desinfectant.create') }}">+ Ajouter une non-conformité</a>
    </div>

    <div class="table-scroll">
    <table class="table table-clean">
        <thead>
            <tr>
                <th>Type</th>
                <th>Article</th>
                <th class="text-right">Stock</th>
                <th>Expiration</th>
                <th>Code UM</th>
                <th class="text-right">Pal.</th>
                <th class="text-right">Prix unit.</th>
                <th class="text-right">Total</th>
                <th class="text-right">Poids/carton</th>
                <th class="text-right">Poids total (kg)</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td><span class="badge {{ typeBadge($r->type_article) }}">{{ $r->type_article ?? '—' }}</span></td>
                    <td class="cell-article">
                        <span class="art-num">{{ $r->numero_article }}</span>
                        <span class="art-desc">{{ $r->description_article }}</span>
                    </td>
                    <td class="text-right num">{{ number_format($r->stock_mag, 0, ',', ' ') }}</td>
                    <td class="nowrap">
                        @if (! $r->date_expiration)
                            <span class="muted">aucune date</span>
                        @elseif ($r->is_expired)
                            <span class="date-expired">{{ $r->date_expiration->format('d/m/Y') }}</span>
                        @else
                            {{ $r->date_expiration->format('d/m/Y') }}
                        @endif
                    </td>
                    <td class="nowrap">{{ $r->code_um }}</td>
                    <td class="text-right num">{{ $r->nbr_palettes }}</td>
                    <td class="text-right num">{{ number_format($r->prix_unitaire, 2, ',', ' ') }}</td>
                    <td class="text-right num">{{ number_format($r->total, 2, ',', ' ') }}</td>
                    <td class="text-right num">{{ number_format($r->poids_par_carton_kg, 3, ',', ' ') }}</td>
                    <td class="text-right num">{{ number_format($r->poids_total_kg, 2, ',', ' ') }}</td>
                    <td class="nowrap">
                        @if ($r->motif)<span class="badge badge-danger">{{ $r->motif }}</span>@endif
                        @if ($r->responsable)<span class="cell-sub">{{ $r->responsable }}</span>@endif
                    </td>
                    <td class="actions nowrap">
                        <a href="{{ route('desinfectant.edit', $r) }}">Modifier</a>
                        <form action="{{ route('desinfectant.destroy', $r) }}" method="post" class="inline-form"
                              onsubmit="return confirm('Supprimer cette ligne ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12">Aucune non-conformité enregistrée.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">TOTAL</td>
                <td class="text-right num">{{ $totaux['palettes'] }}</td>
                <td></td>
                <td class="text-right num">{{ number_format($totaux['valeur'], 2, ',', ' ') }}</td>
                <td></td>
                <td class="text-right num">{{ number_format($totaux['poids_kg'], 2, ',', ' ') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    </div>
@endsection
