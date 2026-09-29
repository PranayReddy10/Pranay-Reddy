<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Browsers silently merge a <form> nested inside another <form>. When that inner form was a
 * delete form, its _method=DELETE hijacked the edit form and "Save" deleted the record.
 */
class FormMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_page_nests_a_form_inside_another_form(): void
    {
        Carbon::setTestNow('2026-09-28');
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create());

        $pages = ['/', '/renewals', '/reports', '/settings', '/personal', '/personal/create', '/personal/1/edit', '/invoices/1'];
        foreach (['accounts', 'projects', 'domains', 'servers', 'clients', 'partners', 'transactions', 'invoices'] as $resource) {
            $pages[] = "/$resource/create";
            $pages[] = "/$resource/1/edit";
        }

        foreach ($pages as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            $depth = 0;
            preg_match_all('~<(/?)form\b~i', $html, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                $depth += $tag[1] === '/' ? -1 : 1;
                $this->assertLessThanOrEqual(1, $depth, "Nested <form> on $page");
            }
            $this->assertSame(0, $depth, "Unbalanced <form> tags on $page");
        }
    }

    public function test_updating_a_record_keeps_it(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::factory()->create());

        $this->put('/accounts/1', ['label' => 'Renamed', 'provider' => 'GoDaddy', 'type' => 'registrar'])
            ->assertRedirect('/accounts/1');
        $this->assertDatabaseHas('accounts', ['id' => 1, 'label' => 'Renamed']);
    }
}
