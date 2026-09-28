@php $from = ($profile['business_name'] ?? null) ?: (($profile['owner_name'] ?? null) ?: config('app.name')); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $invoice->number }} · {{ $from }}</title>
    <meta property="og:title" content="{{ $invoice->number }} from {{ $from }}">
    <meta property="og:description" content="Amount {{ money($invoice->total()) }} · Balance due {{ money($invoice->balance()) }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="bill-page">
    <div class="bill-wrap">
        <div class="bill-toolbar no-print">
            <button type="button" class="btn" onclick="window.print()"><x-icon name="download"/> Download / print PDF</button>
        </div>
        @include('invoices._document')
        <p class="muted no-print" style="text-align:center;font-size:12px">Thank you for your business.</p>
    </div>
</body>
</html>
