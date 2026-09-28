<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Ledger
{
    public static function money(float|int|string|null $amount, bool $signed = false): string
    {
        $amount = (float) $amount;
        $formatted = config('ledger.currency').number_format(abs($amount), 2);

        if ($amount < 0) {
            return '-'.$formatted;
        }

        return $signed && $amount > 0 ? '+'.$formatted : $formatted;
    }

    public static function cycleMonths(?string $cycle): int
    {
        return (int) config("ledger.billing_cycles.$cycle.months", 0);
    }

    public static function cycleLabel(?string $cycle): string
    {
        return config("ledger.billing_cycles.$cycle.label", 'One-time / none');
    }

    /** Normalise a recurring amount to a yearly figure. */
    public static function yearly(float|string $amount, ?string $cycle): float
    {
        $months = self::cycleMonths($cycle);

        return $months ? round((float) $amount * (12 / $months), 2) : 0.0;
    }

    public static function monthly(float|string $amount, ?string $cycle): float
    {
        return round(self::yearly($amount, $cycle) / 12, 2);
    }

    /** Move a due date forward by one billing cycle. */
    public static function advance(?CarbonInterface $from, ?string $cycle): Carbon
    {
        $base = $from ? Carbon::parse($from) : Carbon::today();

        return $base->copy()->addMonthsNoOverflow(max(self::cycleMonths($cycle), 1));
    }

    public static function categoryLabel(string $category): string
    {
        return config("ledger.income_categories.$category")
            ?? config("ledger.expense_categories.$category")
            ?? str($category)->replace('_', ' ')->title()->toString();
    }

    /**
     * Human "due" status for a date: overdue, soon, ok.
     *
     * @return array{0:string,1:string} [state, label]
     */
    public static function dueState(?CarbonInterface $date): array
    {
        if (! $date) {
            return ['none', '—'];
        }

        $days = (int) Carbon::today()->diffInDays($date, false);

        return match (true) {
            $days < 0 => ['overdue', abs($days).'d overdue'],
            $days === 0 => ['soon', 'Due today'],
            $days <= config('ledger.due_soon_days') => ['soon', "in {$days}d"],
            default => ['ok', "in {$days}d"],
        };
    }

    /** UPI deep link for "pay this bill" (works in GPay, PhonePe, Paytm…). */
    public static function upiLink(?string $upiId, ?string $payee, float $amount, string $note): ?string
    {
        if (blank($upiId)) {
            return null;
        }

        return 'upi://pay?'.http_build_query(array_filter([
            'pa' => $upiId,
            'pn' => $payee,
            'am' => $amount > 0 ? number_format($amount, 2, '.', '') : null,
            'cu' => 'INR',
            'tn' => $note,
        ]), '', '&', PHP_QUERY_RFC3986);
    }

    public static function qrSvg(string $data, int $size = 180): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($data);
    }
}
