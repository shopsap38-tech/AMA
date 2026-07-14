@extends('layouts.app')
@section('title', 'Dashboard - Suivi des non-conformités')

@section('content')
    <h2>Dashboard &ndash; Suivi des non-conformités</h2>
    <p class="sub" style="color:var(--muted);">Situation au {{ now()->format('d/m/Y') }}</p>

    <div class="cards">
        <div class="card">
            <h3>PF Semi-fini-matic</h3>
            <div class="metric">{{ $sf['palettes'] }}</div>
            <div class="sub">palettes non conformes</div>
            <div class="sub">{{ number_format($sf['poids_kg'], 0, ',', ' ') }} kg &middot; {{ $sf['quantite'] }} unités</div>
        </div>
        <div class="card">
            <h3>Désinfectant</h3>
            <div class="metric">{{ $de['palettes'] }}</div>
            <div class="sub">palettes non conformes</div>
            <div class="sub">{{ number_format($de['poids_kg'], 0, ',', ' ') }} kg &middot; {{ number_format($de['total_valeur'], 2, ',', ' ') }} DH</div>
        </div>
        <div class="card">
            <h3>Big Bag</h3>
            <div class="metric">{{ $bb['big_bags'] }}</div>
            <div class="sub">big bags non conformes</div>
            <div class="sub">{{ number_format($bb['tonnage'], 0, ',', ' ') }} kg</div>
        </div>
    </div>

    <h3 class="section-title">Graphique</h3>
    <div class="chart-card chart-hero">
        <h4>Palettes / big bags par catégorie</h4>
        {!! \App\Support\Chart::barChartPro($chartPalettes, 'unités') !!}
    </div>

    <h3 class="section-title">Synthèse détaillée</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th class="text-right">Lignes</th>
                <th class="text-right">Palettes / Big bags</th>
                <th class="text-right">Poids total (kg)</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>PF Semi-fini-matic</td>
                <td class="text-right">{{ $sf['lignes'] }}</td>
                <td class="text-right">{{ $sf['palettes'] }}</td>
                <td class="text-right">{{ number_format($sf['poids_kg'], 2, ',', ' ') }}</td>
                <td><a href="{{ route('semi-fini.index') }}">Détails &rarr;</a></td>
            </tr>
            <tr>
                <td>Désinfectant</td>
                <td class="text-right">{{ $de['lignes'] }}</td>
                <td class="text-right">{{ $de['palettes'] }}</td>
                <td class="text-right">{{ number_format($de['poids_kg'], 2, ',', ' ') }}</td>
                <td><a href="{{ route('desinfectant.index') }}">Détails &rarr;</a></td>
            </tr>
            <tr>
                <td>Big Bag</td>
                <td class="text-right">{{ $bb['lignes'] }}</td>
                <td class="text-right">{{ $bb['big_bags'] }}</td>
                <td class="text-right">{{ number_format($bb['tonnage'], 2, ',', ' ') }}</td>
                <td><a href="{{ route('big-bag.index') }}">Détails &rarr;</a></td>
            </tr>
        </tbody>
    </table>
@endsection
