@props(['title', 'crumb' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body>
@php
    $nav = [
        'Overview' => [
            ['dashboard', 'Dashboard', 'home', 'dashboard'],
            ['renewals', 'Renewals & dues', 'calendar', 'renewals'],
            ['reports', 'Spending analysis', 'chart', 'reports'],
        ],
        'Work' => [
            ['projects.index', 'Projects', 'folder', 'projects.*'],
            ['clients.index', 'Clients', 'users', 'clients.*'],
            ['partners.index', 'Partners', 'handshake', 'partners.*'],
        ],
        'Infrastructure' => [
            ['domains.index', 'Domains', 'globe', 'domains.*'],
            ['servers.index', 'Hosting / servers', 'server', 'servers.*'],
            ['accounts.index', 'Accounts', 'key', 'accounts.*'],
        ],
        'Money' => [
            ['transactions.index', 'Transactions', 'list', 'transactions.*'],
            ['invoices.index', 'Bills', 'receipt', 'invoices.*'],
        ],
        'Personal' => [
            ['personal.index', 'Personal spending', 'wallet', 'personal.*'],
            ['settings', 'Settings', 'settings', 'settings'],
        ],
    ];
@endphp
<div class="mobile-bar">
    <button type="button" data-nav-toggle aria-label="Open menu"><x-icon name="menu" width="24" height="24"/></button>
    <strong>{{ config('app.name') }}</strong>
</div>
<div class="scrim" data-nav-toggle></div>

<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand"><img src="{{ asset('icons/icon-192.png') }}" alt="">{{ config('app.name') }}</a>
        @foreach ($nav as $group => $items)
            <div class="nav-group">{{ $group }}</div>
            <nav class="nav">
                @foreach ($items as [$route, $label, $icon, $pattern])
                    <a href="{{ route($route) }}" @class(['active' => request()->routeIs($pattern)])><x-icon :name="$icon"/> {{ $label }}</a>
                @endforeach
            </nav>
        @endforeach
        <div class="sidebar-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><x-icon name="logout"/> Sign out</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                @if ($crumb)<div class="crumb">{!! $crumb !!}</div>@endif
                <h1>{{ $title }}</h1>
            </div>
            @isset($actions)<div class="actions">{{ $actions }}</div>@endisset
        </header>
        <div class="content">
            @if (session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif
            @if ($errors->any() && ! isset($suppressErrors))<div class="alert alert-err">Please fix the highlighted fields.</div>@endif
            {{ $slot }}
        </div>
    </main>
</div>

<nav class="bottom-nav">
    <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])><x-icon name="home"/>Home</a>
    <a href="{{ route('renewals') }}" @class(['active' => request()->routeIs('renewals')])><x-icon name="calendar"/>Dues</a>
    <a href="{{ route('transactions.create') }}" class="fab"><x-icon name="plus"/>Add</a>
    <a href="{{ route('personal.index') }}" @class(['active' => request()->routeIs('personal.*')])><x-icon name="wallet"/>Personal</a>
    <a href="{{ route('reports') }}" @class(['active' => request()->routeIs('reports')])><x-icon name="chart"/>Reports</a>
</nav>

<div class="install-banner" id="install-banner">
    <span>Install {{ config('app.name') }} as an app?</span>
    <button type="button" class="btn btn-primary btn-sm" id="install-btn">Install</button>
    <button type="button" class="btn btn-sm" id="install-dismiss">Later</button>
</div>

<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
