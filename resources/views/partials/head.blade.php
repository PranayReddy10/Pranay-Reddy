<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0a0c10" media="(prefers-color-scheme: dark)">
<script>
    {{-- Apply the saved theme before first paint to avoid a flash. --}}
    try {
        var t = localStorage.getItem('theme');
        if (t === 'light' || t === 'dark') {
            document.documentElement.dataset.theme = t;
            document.querySelectorAll('meta[name=theme-color]').forEach(function (m) { m.content = t === 'dark' ? '#0a0c10' : '#ffffff'; });
        }
    } catch (e) {}
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
