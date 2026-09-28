<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['label', 'provider', 'type', 'login_email', 'dashboard_url', 'notes'];

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->provider} · {$this->label}";
    }
}
