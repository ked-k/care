<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Shops') }} | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=40">
</head>
<body class="shop">
    {{-- Batch 40: only shown when more than one agency has a live shop. --}}
    <main class="s-wrap" style="padding:48px 20px">
        <h1 class="s-page-title">{{ __('Choose a shop') }}</h1>
        <div class="s-addr-grid s-mt">
            @foreach ($stores as $s)
                <a href="{{ route('shop.home', $s->slug) }}" class="s-addr-card">
                    <strong style="font-size:18px">{{ $s->name }}</strong>
                    @if ($s->tagline)<span class="s-muted">{{ $s->tagline }}</span>@endif
                </a>
            @endforeach
        </div>
    </main>
</body>
</html>
