<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'project_id', 'client_id', 'title', 'issue_date', 'due_date', 'status',
        'discount', 'tax_percent', 'tax_label', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'discount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->share_token ??= Str::random(40);
            $invoice->number ??= static::nextNumber();
        });
    }

    public static function nextNumber(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV').'-'.now()->year.'-';
        $last = static::where('number', 'like', $prefix.'%')->orderByDesc('id')->value('number');
        $seq = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        do {
            $number = $prefix.str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);
        } while (static::where('number', $number)->exists());

        return $number;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('type', 'income')->orderBy('date');
    }

    /** Invoices that still count toward what clients owe. */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', ['draft', 'cancelled']);
    }

    public function subtotal(): float
    {
        return round($this->items->sum(fn (InvoiceItem $i) => $i->amount()), 2);
    }

    public function taxAmount(): float
    {
        return round(max($this->subtotal() - (float) $this->discount, 0) * (float) $this->tax_percent / 100, 2);
    }

    public function total(): float
    {
        return round(max($this->subtotal() - (float) $this->discount, 0) + $this->taxAmount(), 2);
    }

    public function paid(): float
    {
        return round((float) $this->payments->sum('amount'), 2);
    }

    public function balance(): float
    {
        return $this->status === 'cancelled' ? 0.0 : round(max($this->total() - $this->paid(), 0), 2);
    }

    /** draft | cancelled | paid | partial | overdue | unpaid */
    public function state(): string
    {
        return match (true) {
            in_array($this->status, ['draft', 'cancelled'], true) => $this->status,
            $this->total() > 0 && $this->balance() <= 0 => 'paid',
            $this->due_date && $this->due_date->lt(Carbon::today()) => 'overdue',
            $this->paid() > 0 => 'partial',
            default => 'unpaid',
        };
    }

    public function stateLabel(): string
    {
        return ['draft' => 'Draft', 'cancelled' => 'Cancelled', 'paid' => 'Paid', 'partial' => 'Part-paid', 'overdue' => 'Overdue', 'unpaid' => 'Unpaid'][$this->state()];
    }

    public function stateBadge(): string
    {
        return ['paid' => 'badge-in', 'partial' => 'badge-soon', 'overdue' => 'badge-overdue', 'unpaid' => 'badge-brand'][$this->state()] ?? '';
    }

    public function shareUrl(): string
    {
        return route('bills.public', $this->share_token);
    }

    public function billTo(): ?string
    {
        return $this->client?->company ?: $this->client?->name;
    }
}
