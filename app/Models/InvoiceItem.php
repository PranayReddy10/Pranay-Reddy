<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['description', 'quantity', 'rate', 'position'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'rate' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function amount(): float
    {
        return round((float) $this->quantity * (float) $this->rate, 2);
    }
}
