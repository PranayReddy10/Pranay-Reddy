<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalExpense extends Model
{
    protected $fillable = ['date', 'category', 'amount', 'payment_method', 'paid_to', 'description'];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'decimal:2'];
    }

    public function getCategoryLabelAttribute(): string
    {
        return config("ledger.personal_categories.{$this->category}", ucfirst($this->category));
    }
}
