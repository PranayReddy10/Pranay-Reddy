<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Offline'])
</head>
<body>
<div class="auth">
    <div class="card">
        <div class="card-body" style="text-align:center">
            <img src="{{ asset('icons/icon-192.png') }}" alt="" width="56" height="56" style="border-radius:14px">
            <h1 style="margin-top:12px">You're offline</h1>
            <p class="muted">Pages you opened recently are still available. Reconnect to see live numbers or save changes.</p>
            <button type="button" class="btn btn-primary" onclick="location.reload()">Try again</button>
        </div>
    </div>
</div>
</body>
</html>
