@extends('layouts.app')
@section('title', ($item->exists ? 'Modifier' : 'Ajouter') . ' - PF Semi-fini-matic')

@section('content')
    <h2>{{ $item->exists ? 'Modifier' : 'Ajouter' }} une non-conformité &ndash; Semi-fini</h2>

    <form class="form" method="post"
          action="{{ $item->exists ? route('semi-fini.update', $item) : route('semi-fini.store') }}">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="row">
            <div class="field">
                <label for="numero_article">N° article</label>
                <input type="text" id="numero_article" name="numero_article" required
                       value="{{ old('numero_article', $item->numero_article) }}">
            </div>
            <div class="field" style="flex:2;">
                <label for="description_article">Description article</label>
                <input type="text" id="description_article" name="description_article" required
                       value="{{ old('description_article', $item->description_article) }}">
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="quantite">Quantité</label>
                <input type="number" id="quantite" name="quantite" min="0" required
                       value="{{ old('quantite', $item->quantite) }}">
            </div>
            <div class="field">
                <label for="nbr_palettes">Nbr de palettes</label>
                <input type="number" id="nbr_palettes" name="nbr_palettes" min="0" required
                       value="{{ old('nbr_palettes', $item->nbr_palettes) }}">
            </div>
            <div class="field">
                <label for="poids_total_kg">Poids total (kg)</label>
                <input type="number" step="0.01" id="poids_total_kg" name="poids_total_kg" min="0" required
                       value="{{ old('poids_total_kg', $item->poids_total_kg) }}">
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="motif">Motif</label>
                <input type="text" id="motif" name="motif" value="{{ old('motif', $item->motif) }}">
            </div>
            <div class="field">
                <label for="decision_sq">Décision SQ</label>
                <input type="text" id="decision_sq" name="decision_sq" value="{{ old('decision_sq', $item->decision_sq) }}">
            </div>
        </div>

        <button type="submit" class="btn">Enregistrer</button>
        <a href="{{ route('semi-fini.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
@endsection
