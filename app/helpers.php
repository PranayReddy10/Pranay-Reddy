<?php

use App\Support\Ledger;

if (! function_exists('money')) {
    function money(float|int|string|null $amount, bool $signed = false): string
    {
        return Ledger::money($amount, $signed);
    }
}

if (! function_exists('pct')) {
    function pct(float|int|string|null $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    }
}
