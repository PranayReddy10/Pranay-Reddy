<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PersonalExpense;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Finance;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingAndPersonalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-28');
        $this->user = User::factory()->create();
    }

    private function project(): Project
    {
        $client = Client::create(['name' => 'Kiran', 'company' => 'Rao Constructions', 'phone' => '9848000001']);

        return Project::create(['name' => 'Rao site', 'client_id' => $client->id, 'build_fee' => 30000]);
    }

    public function test_new_screens_render_with_demo_data(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs($this->user);

        foreach (['/', '/renewals', '/invoices', '/invoices?state=due', '/invoices/create', '/invoices/create?project=3&final=1',
            '/invoices/1', '/invoices/1/edit', '/personal', '/personal?month=2026-05', '/personal?month=bad', '/personal/create', '/settings', '/clients/3', '/projects/3'] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_bill_totals_with_discount_tax_and_part_payment(): void
    {
        $project = $this->project();
        $this->actingAs($this->user);

        $this->post(route('invoices.store'), [
            'project_id' => $project->id,
            'issue_date' => '2026-09-20',
            'due_date' => '2026-10-05',
            'status' => 'sent',
            'discount' => 1000,
            'tax_percent' => 18,
            'tax_label' => 'GST',
            'items' => [
                ['description' => 'Website', 'quantity' => 1, 'rate' => 25000],
                ['description' => 'Extra pages', 'quantity' => 2, 'rate' => 3000],
                ['description' => '', 'quantity' => 1, 'rate' => 999], // blank lines are dropped
            ],
        ])->assertRedirect();

        $invoice = Invoice::with('items')->first();
        $this->assertSame($project->client_id, $invoice->client_id, 'client comes from the project');
        $this->assertCount(2, $invoice->items);
        $this->assertSame(31000.0, $invoice->subtotal());
        $this->assertSame(5400.0, $invoice->taxAmount());   // 18% of 30000
        $this->assertSame(35400.0, $invoice->total());
        $this->assertSame('INV-2026-001', $invoice->number);

        $this->post(route('invoices.pay', $invoice), ['amount' => 20000, 'date' => '2026-09-25', 'category' => 'build_fee'])->assertRedirect();

        $invoice->refresh()->load(['items', 'payments']);
        $this->assertSame(15400.0, $invoice->balance());
        $this->assertSame('partial', $invoice->state());
        $this->assertDatabaseHas('transactions', ['invoice_id' => $invoice->id, 'project_id' => $project->id, 'type' => 'income', 'amount' => 20000]);

        $summary = $project->billingSummary();
        $this->assertSame(35400.0, $summary['billed']);
        $this->assertSame(20000.0, $summary['received']);
        $this->assertSame(15400.0, $summary['balance']);
        $this->assertSame(15400.0, app(Finance::class)->outstanding());

        $this->post(route('invoices.pay', $invoice), ['amount' => 15400, 'date' => '2026-09-28', 'category' => 'build_fee']);
        $this->assertSame('paid', $invoice->fresh()->state());
    }

    public function test_earlier_advance_can_be_linked_to_final_bill(): void
    {
        $project = $this->project();
        $advance = Transaction::create(['date' => '2026-06-01', 'type' => 'income', 'category' => 'build_fee', 'amount' => 10000, 'project_id' => $project->id]);
        $invoice = Invoice::create(['project_id' => $project->id, 'client_id' => $project->client_id, 'issue_date' => '2026-09-20']);
        $invoice->items()->create(['description' => 'Website', 'quantity' => 1, 'rate' => 30000]);

        $this->actingAs($this->user)->get(route('invoices.show', $invoice))->assertSee('Earlier project payments');
        $this->post(route('invoices.link', $invoice), ['transaction_id' => $advance->id])->assertRedirect();

        $this->assertSame(20000.0, $invoice->fresh()->balance());

        $this->delete(route('invoices.unlink', [$invoice, $advance]));
        $this->assertNull($advance->fresh()->invoice_id);
    }

    public function test_public_share_link_shows_bill_without_login_and_can_be_reset(): void
    {
        Setting::put(['business_name' => 'PR Web Studio', 'upi_id' => 'pranay@upi']);
        $invoice = Invoice::create(['client_id' => $this->project()->client_id, 'issue_date' => '2026-09-20']);
        $invoice->items()->create(['description' => 'Website', 'quantity' => 1, 'rate' => 5000]);

        $this->get('/bill/'.$invoice->share_token)
            ->assertOk()
            ->assertSee('PR Web Studio')
            ->assertSee('Rao Constructions')
            ->assertSee('upi://pay?pa=pranay%40upi', false)
            ->assertDontSee('Sign out');

        $this->get('/bill/not-a-real-token')->assertNotFound();

        $old = $invoice->share_token;
        $this->actingAs($this->user)->post(route('invoices.regenerate', $invoice));
        auth()->logout();
        $this->get('/bill/'.$old)->assertNotFound();
        $this->get('/bill/'.$invoice->fresh()->share_token)->assertOk();
    }

    public function test_draft_bills_are_not_public_or_counted(): void
    {
        $invoice = Invoice::create(['issue_date' => '2026-09-20', 'status' => 'draft']);
        $invoice->items()->create(['description' => 'Website', 'quantity' => 1, 'rate' => 5000]);

        $this->get('/bill/'.$invoice->share_token)->assertNotFound();
        $this->assertSame(0.0, app(Finance::class)->outstanding());
    }

    public function test_bill_numbers_follow_prefix_and_increment(): void
    {
        Setting::put(['invoice_prefix' => 'PRW']);
        $a = Invoice::create(['issue_date' => '2026-09-20']);
        $b = Invoice::create(['issue_date' => '2026-09-21']);

        $this->assertSame('PRW-2026-001', $a->number);
        $this->assertSame('PRW-2026-002', $b->number);
    }

    public function test_personal_spending_is_tracked_separately_from_business(): void
    {
        $this->actingAs($this->user);
        Transaction::create(['date' => '2026-09-05', 'type' => 'income', 'category' => 'build_fee', 'amount' => 20000]);

        $this->post(route('personal.store'), ['date' => '2026-09-10', 'category' => 'food', 'amount' => 3000])->assertRedirect();
        $this->post(route('personal.store'), ['date' => '2026-09-12', 'category' => 'rent', 'amount' => 12000]);
        $this->post(route('personal.budgets'), ['budgets' => ['food' => 2500, 'rent' => '']]);

        $this->assertSame(2, PersonalExpense::count());
        $this->assertDatabaseHas('personal_budgets', ['category' => 'food', 'monthly_limit' => 2500]);
        $this->assertDatabaseMissing('personal_budgets', ['category' => 'rent']);

        // Business books are untouched.
        $summary = app(Finance::class)->summary(Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30'));
        $this->assertSame(0.0, $summary['spending']);
        $this->assertSame(20000.0, $summary['net']);

        $this->get(route('personal.index', ['month' => '2026-09']))
            ->assertOk()
            ->assertViewHas('total', 15000.0)
            ->assertViewHas('savings', 5000.0);

        $this->get(route('personal.index', ['month' => '2026-08']))->assertViewHas('total', 0.0);
    }

    public function test_personal_expense_validation(): void
    {
        $this->actingAs($this->user)
            ->post(route('personal.store'), ['date' => '2026-09-10', 'category' => 'not-a-category', 'amount' => 0])
            ->assertSessionHasErrors(['category', 'amount']);
    }
}
