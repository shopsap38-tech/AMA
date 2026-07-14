@extends('layouts.app')
@section('title', ($item->exists ? 'Modifier' : 'Ajouter') . ' - Big Bag')

@section('content')
    <h2>{{ $item->exists ? 'Modifier' : 'Ajouter' }} une non-conformité &ndash; Big Bag</h2>

    <form class="form" method="post"
          action="{{ $item->exists ? route('big-bag.update', $item) : route('big-bag.store') }}">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="row">
            <div class="field">
                <label for="date_nc">Date</label>
                <input type="date" id="date_nc" name="date_nc" required
                       value="{{ old('date_nc', optional($item->date_nc)->format('Y-m-d')) }}">
            </div>
            <div class="field">
                <label for="total_big_bag">Total big bags</label>
                <input type="number" id="total_big_bag" name="total_big_bag" min="0" required
                       value="{{ old('total_big_bag', $item->total_big_bag) }}">
            </div>
            <div class="field">
                <label for="tonnage">Tonnage (kg)</label>
                <input type="number" step="0.01" id="tonnage" name="tonnage" min="0" required
                       value="{{ old('tonnage', $item->tonnage) }}">
            </div>
        </div>

        <button type="submit" class="btn">Enregistrer</button>
        <a href="{{ route('big-bag.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
@endsection
