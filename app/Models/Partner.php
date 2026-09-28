<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'upi_or_bank', 'notes'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** Total ad share earned on this partner's projects. */
    public function shareEarned(): float
    {
        return (float) Transaction::income()
            ->whereIn('project_id', $this->projects()->select('id'))
            ->sum('partner_share');
    }

    public function paidOut(): float
    {
        return (float) Transaction::expense()
            ->where('category', 'partner_payout')
            ->where('partner_id', $this->id)
            ->sum('amount');
    }

    /** What I still owe this partner. */
    public function balance(): float
    {
        return round($this->shareEarned() - $this->paidOut(), 2);
    }
}
