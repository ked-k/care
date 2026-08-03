<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="description" content="{{ config('app.description') }}">
<meta name="keywords" content="Laravel admin template, Tailwind admin, roles and permissions, Laravel 12, Alpine.js, admin dashboard">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="icon" type="image/svg+xml" href="{{ asset('img/radminly-mark.svg') }}" />
<link rel="alternate icon" href="{{ asset('favicon.png') }}" />

<!-- Nunito font (kept from the original theme) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">

<!-- Icon fonts (Bootstrap-independent, kept so existing icon markup keeps working) -->
<link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/icon-kit/dist/css/iconkit.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/ionicons/dist/css/ionicons.min.css') }}">

<!-- Tailwind + Alpine (Vite) -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Apply saved theme overrides before paint (avoids flash of default theme) -->
<script>
    (function () {
        try {
            var r = document.documentElement;
            if (localStorage.getItem('radmin-dark') === '1') r.classList.add('dark');
            var t = JSON.parse(localStorage.getItem('radmin-theme') || '{}');
            for (var k in t) { r.style.setProperty(k, t[k]); }
        } catch (e) {}
    })();
</script>

<!-- Stack for page-specific css / head elements -->
@stack('head')
