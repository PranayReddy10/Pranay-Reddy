<?php

namespace App\Models;

use App\Support\Ledger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'date', 'type', 'category', 'amount', 'partner_share', 'project_id', 'client_id',
        'partner_id', 'account_id', 'domain_id', 'server_id', 'invoice_id', 'payment_method', 'reference', 'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'partner_share' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeIncome(Builder $query): void
    {
        $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): void
    {
        $query->where('type', 'expense');
    }

    /** Expenses that are real costs to me (partner payouts just settle a share I already excluded). */
    public function scopeSpending(Builder $query): void
    {
        $query->where('type', 'expense')->where('category', '!=', 'partner_payout');
    }

    public function scopeBetween(Builder $query, $from, $to): void
    {
        $query->whereBetween('date', [$from, $to]);
    }

    public function getCategoryLabelAttribute(): string
    {
        return Ledger::categoryLabel($this->category);
    }

    /** What actually stays with me from this row. */
    public function myAmount(): float
    {
        return $this->type === 'income'
            ? (float) $this->amount - (float) $this->partner_share
            : -(float) $this->amount;
    }
}
