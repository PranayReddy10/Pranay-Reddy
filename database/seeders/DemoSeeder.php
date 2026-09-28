<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Server;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Sample data to explore the app: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        $godaddy = Account::create(['label' => 'Personal', 'provider' => 'GoDaddy', 'type' => 'registrar', 'login_email' => 'me@gmail.com', 'dashboard_url' => 'https://dcc.godaddy.com']);
        $namecheap = Account::create(['label' => 'Clients', 'provider' => 'Namecheap', 'type' => 'registrar', 'login_email' => 'work@gmail.com', 'dashboard_url' => 'https://ap.www.namecheap.com']);
        $hostinger = Account::create(['label' => 'Business', 'provider' => 'Hostinger', 'type' => 'both', 'login_email' => 'work@gmail.com', 'dashboard_url' => 'https://hpanel.hostinger.com']);
        $do = Account::create(['label' => 'Main', 'provider' => 'DigitalOcean', 'type' => 'hosting', 'login_email' => 'me@gmail.com', 'dashboard_url' => 'https://cloud.digitalocean.com']);
        $adsense = Account::create(['label' => 'Publisher', 'provider' => 'Google AdSense', 'type' => 'ads', 'login_email' => 'me@gmail.com']);

        $shared = Server::create(['name' => 'Hostinger Business Shared', 'account_id' => $hostinger->id, 'plan' => 'Business Web Hosting', 'billing_cycle' => 'yearly', 'cost' => 4788, 'next_due_date' => $today->copy()->addDays(45), 'location' => 'Mumbai']);
        $vps = Server::create(['name' => 'DO Droplet blr1', 'account_id' => $do->id, 'plan' => '2 vCPU / 4GB', 'ip_address' => '139.59.10.24', 'billing_cycle' => 'monthly', 'cost' => 2000, 'next_due_date' => $today->copy()->addDays(6), 'location' => 'Bangalore', 'auto_renew' => true]);

        $ravi = Client::create(['name' => 'Ravi Kumar', 'company' => 'Sri Sai Dental Clinic', 'phone' => '+91 98480 00001']);
        $anita = Client::create(['name' => 'Anita Sharma', 'company' => 'Anita Boutique', 'phone' => '+91 98480 00002']);
        $kiran = Client::create(['name' => 'Kiran Rao', 'company' => 'Rao Constructions', 'email' => 'kiran@example.com']);

        $friend = Partner::create(['name' => 'Suresh', 'phone' => '+91 90000 11111', 'upi_or_bank' => 'suresh@upi', 'notes' => '40% of ad revenue on the tech blog.']);

        $dental = Project::create(['name' => 'Sai Dental website', 'url' => 'saidentalclinic.in', 'client_id' => $ravi->id, 'type' => 'client', 'build_fee' => 15000, 'billing_cycle' => 'monthly', 'billing_amount' => 800, 'next_billing_date' => $today->copy()->addDays(3), 'started_on' => $today->copy()->subMonths(10)]);
        $boutique = Project::create(['name' => 'Anita Boutique store', 'url' => 'anitaboutique.com', 'client_id' => $anita->id, 'type' => 'client', 'build_fee' => 25000, 'billing_cycle' => 'yearly', 'billing_amount' => 6000, 'next_billing_date' => $today->copy()->addDays(20), 'started_on' => $today->copy()->subMonths(11)]);
        $rao = Project::create(['name' => 'Rao Constructions site', 'url' => 'raoconstructions.in', 'client_id' => $kiran->id, 'type' => 'client', 'build_fee' => 18000, 'billing_cycle' => 'none', 'started_on' => $today->copy()->subMonths(4)]);
        $blog = Project::create(['name' => 'Tech blog', 'url' => 'techtelugu.in', 'partner_id' => $friend->id, 'partner_share_percent' => 40, 'type' => 'ads', 'started_on' => $today->copy()->subYear()]);
        $tools = Project::create(['name' => 'Online tools site', 'url' => 'quickcalc.tools', 'type' => 'ads', 'started_on' => $today->copy()->subMonths(8)]);

        $domains = [
            ['saidentalclinic.in', $dental, $namecheap, $shared, 650, 'me', 25],
            ['anitaboutique.com', $boutique, $namecheap, $shared, 1100, 'client', 70],
            ['raoconstructions.in', $rao, $godaddy, $shared, 799, 'me', 200],
            ['techtelugu.in', $blog, $godaddy, $vps, 699, 'me', 12],
            ['quickcalc.tools', $tools, $hostinger, $vps, 2400, 'me', -3],
        ];
        foreach ($domains as [$name, $project, $account, $server, $cost, $paidBy, $days]) {
            $d = Domain::create(['name' => $name, 'project_id' => $project->id, 'account_id' => $account->id, 'server_id' => $server->id, 'renewal_cost' => $cost, 'paid_by' => $paidBy, 'registered_on' => $today->copy()->addDays($days)->subYear(), 'expires_on' => $today->copy()->addDays($days)]);
            if ($paidBy === 'me') {
                Transaction::create(['date' => $d->registered_on, 'type' => 'expense', 'category' => 'domain', 'amount' => $cost, 'domain_id' => $d->id, 'account_id' => $account->id, 'project_id' => $project->id, 'description' => "Domain purchase: {$name}", 'payment_method' => 'UPI']);
            }
        }

        // Build fees.
        foreach ([[$dental, 15000, 10], [$boutique, 25000, 11], [$rao, 18000, 4]] as [$p, $amount, $monthsAgo]) {
            Transaction::create(['date' => $today->copy()->subMonths($monthsAgo), 'type' => 'income', 'category' => 'build_fee', 'amount' => $amount, 'project_id' => $p->id, 'client_id' => $p->client_id, 'description' => "{$p->name} · build fee", 'payment_method' => 'Bank transfer']);
        }

        for ($m = 11; $m >= 0; $m--) {
            $date = $today->copy()->subMonthsNoOverflow($m)->startOfMonth()->addDays(4);

            if ($m <= 9) {
                Transaction::create(['date' => $date, 'type' => 'income', 'category' => 'client_payment', 'amount' => 800, 'project_id' => $dental->id, 'client_id' => $ravi->id, 'description' => 'Sai Dental · monthly fee', 'payment_method' => 'UPI']);
            }

            Transaction::create(['date' => $date->copy()->addDays(2), 'type' => 'expense', 'category' => 'hosting', 'amount' => 2000, 'server_id' => $vps->id, 'account_id' => $do->id, 'description' => 'Hosting: DO Droplet blr1 (Monthly)', 'payment_method' => 'Card']);

            $blogAds = 3000 + ($m * 137 % 1800);
            Transaction::create(['date' => $date->copy()->addDays(17), 'type' => 'income', 'category' => 'ad_revenue', 'amount' => $blogAds, 'partner_share' => round($blogAds * 0.4, 2), 'project_id' => $blog->id, 'account_id' => $adsense->id, 'description' => 'AdSense payout · Tech blog']);

            if ($m <= 7) {
                Transaction::create(['date' => $date->copy()->addDays(17), 'type' => 'income', 'category' => 'ad_revenue', 'amount' => 1200 + ($m * 211 % 900), 'project_id' => $tools->id, 'account_id' => $adsense->id, 'description' => 'AdSense payout · Tools site']);
            }

            Transaction::create(['date' => $date->copy()->addDays(9), 'type' => 'expense', 'category' => 'software', 'amount' => 1650, 'description' => 'ChatGPT / Figma / tools subscription', 'payment_method' => 'Card']);

            if ($m % 3 === 0) {
                Transaction::create(['date' => $date->copy()->addDays(20), 'type' => 'expense', 'category' => 'partner_payout', 'amount' => 3500, 'partner_id' => $friend->id, 'description' => 'Ad share payout to Suresh', 'payment_method' => 'UPI']);
            }
        }

        Transaction::create(['date' => $today->copy()->subMonths(11), 'type' => 'expense', 'category' => 'hosting', 'amount' => 4788, 'server_id' => $shared->id, 'account_id' => $hostinger->id, 'description' => 'Hostinger Business (Yearly)']);
        Transaction::create(['date' => $today->copy()->subMonths(11), 'type' => 'income', 'category' => 'client_payment', 'amount' => 6000, 'project_id' => $boutique->id, 'client_id' => $anita->id, 'description' => 'Boutique · yearly hosting & maintenance']);
        Transaction::create(['date' => $today->copy()->subMonths(2), 'type' => 'expense', 'category' => 'ads_spend', 'amount' => 2500, 'project_id' => $tools->id, 'description' => 'Facebook promotion']);
        Transaction::create(['date' => $today->copy()->subMonths(5), 'type' => 'income', 'category' => 'marketing', 'amount' => 7000, 'client_id' => $kiran->id, 'description' => 'Google Business profile + SEO setup']);
    }
}
