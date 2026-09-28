<?php

namespace App\Models;

use App\Support\Ledger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'url', 'client_id', 'partner_id', 'partner_share_percent', 'type', 'status',
        'build_fee', 'billing_cycle', 'billing_amount', 'next_billing_date', 'started_on', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'next_billing_date' => 'date',
            'started_on' => 'date',
            'build_fee' => 'decimal:2',
            'billing_amount' => 'decimal:2',
            'partner_share_percent' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('issue_date');
    }

    /**
     * Money owed by the client on this project (ad revenue excluded).
     *
     * @return array{quoted:float,billed:float,received:float,balance:float}
     */
    public function billingSummary(): array
    {
        $billed = $this->invoices()->open()->with(['items'])->get()->sum(fn (Invoice $i) => $i->total());
        $received = (float) $this->transactions()->where('type', 'income')->where('category', '!=', 'ad_revenue')->sum('amount');

        return [
            'quoted' => (float) $this->build_fee,
            'billed' => round($billed, 2),
            'received' => round($received, 2),
            'balance' => round(max($billed - $received, 0), 2),
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function isRecurring(): bool
    {
        return Ledger::cycleMonths($this->billing_cycle) > 0 && $this->billing_amount > 0;
    }

    public function isShared(): bool
    {
        return $this->partner_id && $this->partner_share_percent > 0;
    }

    public function yearlyBilling(): float
    {
        return Ledger::yearly($this->billing_amount, $this->billing_cycle);
    }
}
