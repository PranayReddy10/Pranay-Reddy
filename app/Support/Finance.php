<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\PersonalExpense;
use App\Models\Project;
use App\Models\Server;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only number crunching for the dashboard and reports.
 */
class Finance
{
    /**
     * @return array{income:float,partner_share:float,my_income:float,spending:float,net:float,ad_revenue:float}
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $income = Transaction::income()->between($from, $to);

        $gross = (float) (clone $income)->sum('amount');
        $share = (float) (clone $income)->sum('partner_share');
        $ads = (float) (clone $income)->where('category', 'ad_revenue')->sum('amount');
        $spending = (float) Transaction::spending()->between($from, $to)->sum('amount');

        return [
            'income' => $gross,
            'partner_share' => $share,
            'my_income' => $gross - $share,
            'ad_revenue' => $ads,
            'spending' => $spending,
            'net' => $gross - $share - $spending,
        ];
    }

    /** @return Collection<string,float> category => total, largest first */
    public function byCategory(string $type, Carbon $from, Carbon $to): Collection
    {
        $query = $type === 'income' ? Transaction::income() : Transaction::spending();

        return $query->between($from, $to)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v);
    }

    /**
     * Month-by-month totals between two dates (inclusive).
     *
     * @return Collection<int,array{key:string,label:string,income:float,spending:float,net:float}>
     */
    public function monthly(Carbon $from, Carbon $to): Collection
    {
        $rows = Transaction::between($from->copy()->startOfMonth(), $to->copy()->endOfMonth())
            ->get(['date', 'type', 'category', 'amount', 'partner_share'])
            ->groupBy(fn (Transaction $t) => $t->date->format('Y-m'));

        $months = collect();
        for ($cursor = $from->copy()->startOfMonth(); $cursor->lte($to); $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $group = $rows->get($key, collect());

            $income = $group->where('type', 'income')->sum(fn ($t) => $t->amount - $t->partner_share);
            $spending = $group->where('type', 'expense')->where('category', '!=', 'partner_payout')->sum('amount');

            $months->push([
                'key' => $key,
                'label' => $cursor->format('M y'),
                'income' => round((float) $income, 2),
                'spending' => round((float) $spending, 2),
                'net' => round((float) $income - (float) $spending, 2),
            ]);
        }

        return $months;
    }

    /**
     * Everything with a due date inside the window (and anything already overdue).
     *
     * @return Collection<int,array{kind:string,title:string,subtitle:?string,date:Carbon,amount:float,direction:string,url:string,action:?string,action_label:?string}>
     */
    public function upcoming(int $days = 30): Collection
    {
        $until = Carbon::today()->addDays($days);
        $items = collect();

        Domain::active()->with(['account', 'project'])
            ->whereNotNull('expires_on')->whereDate('expires_on', '<=', $until)
            ->get()
            ->each(fn (Domain $d) => $items->push([
                'kind' => 'domain',
                'title' => $d->name,
                'subtitle' => trim(($d->account?->display_name ?? 'No account').($d->paid_by === 'client' ? ' · client pays' : '')),
                'date' => $d->expires_on,
                'amount' => (float) $d->renewal_cost,
                'direction' => $d->paid_by === 'client' ? 'none' : 'out',
                'url' => route('domains.show', $d),
                'action' => route('domains.renew', $d),
                'action_label' => 'Renewed',
            ]));

        Server::active()->with('account')
            ->whereNotNull('next_due_date')->whereDate('next_due_date', '<=', $until)
            ->get()
            ->each(fn (Server $s) => $items->push([
                'kind' => 'hosting',
                'title' => $s->name,
                'subtitle' => ($s->account?->display_name ?? 'No account').' · '.Ledger::cycleLabel($s->billing_cycle),
                'date' => $s->next_due_date,
                'amount' => (float) $s->cost,
                'direction' => 'out',
                'url' => route('servers.show', $s),
                'action' => route('servers.pay', $s),
                'action_label' => 'Paid',
            ]));

        Project::active()->with('client')
            ->where('billing_cycle', '!=', 'none')->where('billing_amount', '>', 0)
            ->whereNotNull('next_billing_date')->whereDate('next_billing_date', '<=', $until)
            ->get()
            ->each(fn (Project $p) => $items->push([
                'kind' => 'billing',
                'title' => $p->name,
                'subtitle' => ($p->client?->name ?? 'No client').' · '.Ledger::cycleLabel($p->billing_cycle),
                'date' => $p->next_billing_date,
                'amount' => (float) $p->billing_amount,
                'direction' => 'in',
                'url' => route('projects.show', $p),
                'action' => route('projects.collect', $p),
                'action_label' => 'Received',
            ]));

        Invoice::open()->with(['items', 'payments', 'client'])
            ->whereNotNull('due_date')->whereDate('due_date', '<=', $until)
            ->get()
            ->filter(fn (Invoice $i) => $i->balance() > 0)
            ->each(fn (Invoice $i) => $items->push([
                'kind' => 'invoice',
                'title' => "Bill {$i->number}",
                'subtitle' => ($i->billTo() ?? 'No client').' · bill total '.Ledger::money($i->total()),
                'date' => $i->due_date,
                'amount' => $i->balance(),
                'direction' => 'in',
                'url' => route('invoices.show', $i),
                'action' => null,
                'action_label' => null,
            ]));

        return $items->sortBy(fn ($i) => $i['date']->timestamp)->values();
    }

    /** Unpaid balance across all sent bills. */
    public function outstanding(): float
    {
        return round(Invoice::open()->with(['items', 'payments'])->get()->sum(fn (Invoice $i) => $i->balance()), 2);
    }

    public function personalSpending(Carbon $from, Carbon $to): float
    {
        return (float) PersonalExpense::whereBetween('date', [$from, $to])->sum('amount');
    }

    /**
     * What the business looks like on a yearly run-rate.
     *
     * @return array{billing:float,hosting:float,domains:float,net:float}
     */
    public function recurring(): array
    {
        $billing = Project::active()->get()->sum(fn (Project $p) => $p->yearlyBilling());
        $hosting = Server::active()->get()->sum(fn (Server $s) => $s->yearlyCost());
        $domains = (float) Domain::active()->where('paid_by', 'me')->sum('renewal_cost');

        return [
            'billing' => round($billing, 2),
            'hosting' => round($hosting, 2),
            'domains' => round($domains, 2),
            'net' => round($billing - $hosting - $domains, 2),
        ];
    }

    /**
     * Profit & loss per project for a period.
     *
     * @return Collection<int,array{project:Project,income:float,share:float,spending:float,net:float}>
     */
    public function projectBreakdown(Carbon $from, Carbon $to): Collection
    {
        $totals = Transaction::between($from, $to)->whereNotNull('project_id')
            ->selectRaw("project_id,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'income' THEN partner_share ELSE 0 END) as share,
                SUM(CASE WHEN type = 'expense' AND category != 'partner_payout' THEN amount ELSE 0 END) as spending")
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        return Project::with(['client', 'partner'])->whereIn('id', $totals->keys())->get()
            ->map(function (Project $p) use ($totals) {
                $row = $totals[$p->id];

                return [
                    'project' => $p,
                    'income' => (float) $row->income,
                    'share' => (float) $row->share,
                    'spending' => (float) $row->spending,
                    'net' => (float) $row->income - (float) $row->share - (float) $row->spending,
                ];
            })
            ->sortByDesc('net')
            ->values();
    }

    /** @return Collection<int,array{account:Account,total:float,count:int}> */
    public function spendingByAccount(Carbon $from, Carbon $to): Collection
    {
        return Transaction::spending()->between($from, $to)->whereNotNull('account_id')
            ->with('account')
            ->get()
            ->groupBy('account_id')
            ->map(fn ($rows) => [
                'account' => $rows->first()->account,
                'total' => (float) $rows->sum('amount'),
                'count' => $rows->count(),
            ])
            ->sortByDesc('total')
            ->values();
    }
}
