@extends('layouts.app')
@section('title', ($item->exists ? 'Modifier' : 'Ajouter') . ' - Désinfectant')

@section('content')
    <h2>{{ $item->exists ? 'Modifier' : 'Ajouter' }} une non-conformité &ndash; Désinfectant</h2>

    <form class="form" method="post"
          action="{{ $item->exists ? route('desinfectant.update', $item) : route('desinfectant.store') }}">
        @csrf
        @if ($item->exists) @method('PUT') @endif

        <div class="row">
            <div class="field">
                <label for="type_article">Type d'article</label>
                <input type="text" id="type_article" name="type_article" value="{{ old('type_article', $item->type_article) }}">
            </div>
            <div class="field">
                <label for="numero_article">N° article</label>
                <input type="text" id="numero_article" name="numero_article" required value="{{ old('numero_article', $item->numero_article) }}">
            </div>
        </div>

        <div class="field">
            <label for="description_article">Description article</label>
            <input type="text" id="description_article" name="description_article" required value="{{ old('description_article', $item->description_article) }}">
        </div>

        <div class="row">
            <div class="field">
                <label for="stock_mag">Stock au MAG</label>
                <input type="number" id="stock_mag" name="stock_mag" min="0" required value="{{ old('stock_mag', $item->stock_mag) }}">
            </div>
            <div class="field">
                <label for="code_um">Code UM</label>
                <input type="text" id="code_um" name="code_um" value="{{ old('code_um', $item->code_um) }}">
            </div>
            <div class="field">
                <label for="nbr_palettes">Nbr de palettes</label>
                <input type="number" id="nbr_palettes" name="nbr_palettes" min="0" required value="{{ old('nbr_palettes', $item->nbr_palettes) }}">
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="prix_unitaire">Prix unitaire</label>
                <input type="number" step="0.0001" id="prix_unitaire" name="prix_unitaire" min="0" value="{{ old('prix_unitaire', $item->prix_unitaire) }}">
            </div>
            <div class="field">
                <label for="poids_par_carton_kg">Poids par carton (kg)</label>
                <input type="number" step="0.0001" id="poids_par_carton_kg" name="poids_par_carton_kg" min="0" value="{{ old('poids_par_carton_kg', $item->poids_par_carton_kg) }}">
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="date_expiration">Date d'expiration <small>(vide = aucune date)</small></label>
                <input type="date" id="date_expiration" name="date_expiration"
                       value="{{ old('date_expiration', optional($item->date_expiration)->format('Y-m-d')) }}">
            </div>
            <div class="field">
                <label for="date_blocage">Date de blocage</label>
                <input type="date" id="date_blocage" name="date_blocage"
                       value="{{ old('date_blocage', optional($item->date_blocage)->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="motif">Motif</label>
                <input type="text" id="motif" name="motif" value="{{ old('motif', $item->motif) }}">
            </div>
            <div class="field">
                <label for="responsable">Responsable</label>
                <input type="text" id="responsable" name="responsable" value="{{ old('responsable', $item->responsable) }}">
            </div>
            <div class="field">
                <label for="decision_cq">Décision CQ</label>
                <input type="text" id="decision_cq" name="decision_cq" value="{{ old('decision_cq', $item->decision_cq) }}">
            </div>
        </div>

        <button type="submit" class="btn">Enregistrer</button>
        <a href="{{ route('desinfectant.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
@endsection
