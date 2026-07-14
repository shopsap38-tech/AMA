<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Suivi des non-conformités')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        <h1><a href="{{ route('dashboard') }}">Suivi des non-conformités</a></h1>
        <nav>
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('semi-fini.index') }}">PF Semi-fini-matic</a>
            <a href="{{ route('desinfectant.index') }}">Désinfectant</a>
            <a href="{{ route('big-bag.index') }}">Big Bag</a>
        </nav>
    </header>
    <main class="container">
        @if (session('success'))
            <p class="alert alert-success">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul style="margin:0; padding-left:1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
    <footer class="site-footer">
        <p>Application « Suivi des non-conformités » &mdash; Laravel {{ Illuminate\Foundation\Application::VERSION }}</p>
    </footer>
</body>
</html>
