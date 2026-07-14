@extends('layouts.app')
@section('title', 'Big Bag - Non-conformités')

@section('content')
    <div class="toolbar">
        <h2>Big Bag</h2>
        <a class="btn" href="{{ route('big-bag.create') }}">+ Ajouter une non-conformité</a>
    </div>

    <table class="table table-clean">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-right">Total big bags</th>
                <th class="text-right">Tonnage (kg)</th>
                <th class="text-right">Tonnage (T)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="nowrap">{{ $r->date_nc->format('d/m/Y') }}</td>
                    <td class="text-right num">{{ $r->total_big_bag }}</td>
                    <td class="text-right num">{{ number_format($r->tonnage, 2, ',', ' ') }}</td>
                    <td class="text-right num">{{ number_format($r->tonnage_t, 3, ',', ' ') }}</td>
                    <td class="actions nowrap">
                        <a href="{{ route('big-bag.edit', $r) }}">Modifier</a>
                        <form action="{{ route('big-bag.destroy', $r) }}" method="post" class="inline-form"
                              onsubmit="return confirm('Supprimer cette ligne ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Aucune non-conformité enregistrée.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="text-right num">{{ $totaux['big_bags'] }}</td>
                <td class="text-right num">{{ number_format($totaux['tonnage'], 2, ',', ' ') }}</td>
                <td class="text-right num">{{ number_format($totaux['tonnage'] / 1000, 3, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
@endsection
