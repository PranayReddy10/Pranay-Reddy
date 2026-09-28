<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Sign in'])
</head>
<body>
<div class="auth">
    <div class="card">
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
                <img src="{{ asset('icons/icon-192.png') }}" alt="" width="40" height="40" style="border-radius:10px">
                <div>
                    <h1>{{ config('app.name') }}</h1>
                    <small>Private work &amp; finance tracker</small>
                </div>
            </div>
            <form method="POST" action="{{ url('/login') }}" class="stack">
                @csrf
                <x-input name="email" label="Email" type="email" required autofocus autocomplete="username"/>
                <x-input name="password" label="Password" type="password" required autocomplete="current-password"/>
                <x-checkbox name="remember" label="Keep me signed in" :checked="true"/>
                <button type="submit" class="btn btn-primary">Sign in</button>
            </form>
        </div>
    </div>
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
