<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Server;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-28');
        $this->user = User::factory()->create();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_owner_can_log_in(): void
    {
        $this->post('/login', ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_every_screen_renders_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs($this->user);

        $pages = ['/', '/renewals', '/reports', '/reports?year=2025', '/projects', '/projects/create', '/clients', '/clients/create',
            '/partners', '/partners/create', '/accounts', '/accounts/create', '/servers', '/servers/create', '/domains',
            '/domains/create', '/transactions', '/transactions/create', '/transactions/export'];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }

        foreach ([Project::class => 'projects', Domain::class => 'domains', Server::class => 'servers', Account::class => 'accounts', Partner::class => 'partners'] as $model => $prefix) {
            $id = $model::first()->id;
            $this->get("/$prefix/$id")->assertOk();
            $this->get("/$prefix/$id/edit")->assertOk();
        }
    }

    public function test_renewing_a_domain_books_expense_and_extends_expiry(): void
    {
        $account = Account::create(['label' => 'Personal', 'provider' => 'GoDaddy', 'type' => 'registrar']);
        $domain = Domain::create(['name' => 'example.in', 'account_id' => $account->id, 'renewal_cost' => 700, 'expires_on' => '2026-10-10']);

        $this->actingAs($this->user)->post(route('domains.renew', $domain), ['years' => 2])->assertRedirect();

        $this->assertSame('2028-10-10', $domain->fresh()->expires_on->toDateString());
        $this->assertDatabaseHas('transactions', ['domain_id' => $domain->id, 'account_id' => $account->id, 'category' => 'domain', 'amount' => 1400]);
    }

    public function test_client_paid_domain_renewal_books_no_expense(): void
    {
        $domain = Domain::create(['name' => 'client.com', 'renewal_cost' => 900, 'paid_by' => 'client', 'expires_on' => '2026-10-01']);

        $this->actingAs($this->user)->post(route('domains.renew', $domain));

        $this->assertSame('2027-10-01', $domain->fresh()->expires_on->toDateString());
        $this->assertSame(0, Transaction::count());
    }

    public function test_paying_a_server_rolls_due_date_by_its_cycle(): void
    {
        $server = Server::create(['name' => 'VPS', 'billing_cycle' => 'quarterly', 'cost' => 1500, 'next_due_date' => '2026-01-31']);

        $this->actingAs($this->user)->post(route('servers.pay', $server));

        $this->assertSame('2026-04-30', $server->fresh()->next_due_date->toDateString());
        $this->assertDatabaseHas('transactions', ['server_id' => $server->id, 'type' => 'expense', 'category' => 'hosting', 'amount' => 1500]);
    }

    public function test_collecting_client_billing_records_income(): void
    {
        $project = Project::create(['name' => 'Clinic', 'billing_cycle' => 'monthly', 'billing_amount' => 800, 'next_billing_date' => '2026-10-01']);

        $this->actingAs($this->user)->post(route('projects.collect', $project));

        $this->assertSame('2026-11-01', $project->fresh()->next_billing_date->toDateString());
        $this->assertDatabaseHas('transactions', ['project_id' => $project->id, 'type' => 'income', 'category' => 'client_payment', 'amount' => 800]);
    }

    public function test_ad_revenue_on_shared_project_is_split_and_tracked_as_owed(): void
    {
        $partner = Partner::create(['name' => 'Friend']);
        $shared = Project::create(['name' => 'Blog', 'type' => 'ads', 'partner_id' => $partner->id, 'partner_share_percent' => 40]);
        $solo = Project::create(['name' => 'Tools', 'type' => 'ads']);
        $this->actingAs($this->user);

        $this->post(route('transactions.store'), ['type' => 'income', 'category' => 'ad_revenue', 'amount' => 5000, 'date' => '2026-09-01', 'project_id' => $shared->id]);
        $this->post(route('transactions.store'), ['type' => 'income', 'category' => 'ad_revenue', 'amount' => 3000, 'date' => '2026-09-01', 'project_id' => $solo->id]);

        $this->assertEquals(2000, Transaction::where('project_id', $shared->id)->value('partner_share'));
        $this->assertEquals(0, Transaction::where('project_id', $solo->id)->value('partner_share'));
        $this->assertSame(2000.0, $partner->balance());

        $this->post(route('partners.payout', $partner), ['amount' => 1500, 'date' => '2026-09-10']);
        $this->assertSame(500.0, $partner->fresh()->balance());

        // Payouts settle a share that's already excluded, so they aren't counted as spending.
        $this->get(route('reports', ['year' => 2026]))->assertOk()->assertViewHas('summary', fn ($s) => $s['my_income'] == 6000 && $s['spending'] == 0 && $s['net'] == 6000);
    }

    public function test_partner_payout_transaction_requires_partner(): void
    {
        $this->actingAs($this->user)
            ->post(route('transactions.store'), ['type' => 'expense', 'category' => 'partner_payout', 'amount' => 100, 'date' => '2026-09-01'])
            ->assertSessionHasErrors('partner_id');
    }

    public function test_transaction_store_ignores_offsite_redirects(): void
    {
        $this->actingAs($this->user)
            ->post(route('transactions.store'), ['type' => 'expense', 'category' => 'software', 'amount' => 10, 'date' => '2026-09-01', 'redirect_to' => 'http://localhost.evil.test/x'])
            ->assertRedirect(route('transactions.index'));
    }
}
