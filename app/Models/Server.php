<?php

namespace App\Models;

use App\Support\Ledger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'name', 'account_id', 'ip_address', 'plan', 'location', 'billing_cycle',
        'cost', 'next_due_date', 'auto_renew', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'next_due_date' => 'date',
            'auto_renew' => 'boolean',
            'is_active' => 'boolean',
            'cost' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function yearlyCost(): float
    {
        return Ledger::yearly($this->cost, $this->billing_cycle);
    }
}
