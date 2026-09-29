<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0a0c10" media="(prefers-color-scheme: dark)">
<script>
    {{-- Theme (auto / light / dark): applied before first paint, remembered per device. --}}
    (function () {
        var root = document.documentElement;
        var media = window.matchMedia ? matchMedia('(prefers-color-scheme: dark)') : null;
        function saved() { try { return localStorage.getItem('theme'); } catch (e) { return null; } }
        function apply(choice) {
            if (choice === 'light' || choice === 'dark') root.setAttribute('data-theme', choice);
            else { root.removeAttribute('data-theme'); choice = 'auto'; }
            var dark = choice === 'dark' || (choice === 'auto' && media && media.matches);
            document.querySelectorAll('meta[name=theme-color]').forEach(function (m) { m.content = dark ? '#0a0c10' : '#ffffff'; });
            document.querySelectorAll('[data-theme-choice]').forEach(function (b) {
                b.setAttribute('aria-pressed', String(b.getAttribute('data-theme-choice') === choice));
            });
        }
        apply(saved());
        document.addEventListener('DOMContentLoaded', function () { apply(saved()); });
        document.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('[data-theme-choice]');
            if (!btn) return;
            var choice = btn.getAttribute('data-theme-choice');
            try { choice === 'auto' ? localStorage.removeItem('theme') : localStorage.setItem('theme', choice); } catch (err) {}
            apply(choice);
        });
        if (media && media.addEventListener) media.addEventListener('change', function () { if (!saved()) apply('auto'); });
    })();
</script>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<title>{{ $title }} · {{ config('app.name') }}</title>
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
