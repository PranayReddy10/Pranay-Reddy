<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalBudget extends Model
{
    protected $fillable = ['category', 'monthly_limit'];

    protected function casts(): array
    {
        return ['monthly_limit' => 'decimal:2'];
    }
}
