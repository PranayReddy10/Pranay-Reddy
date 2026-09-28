@php $editing = $project->exists; @endphp
<x-layout :title="$editing ? 'Edit '.$project->name : 'New project'" :crumb="'<a href=\''.route('projects.index').'\'>Projects</a>'">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('projects.update', $project) : route('projects.store') }}" class="stack">
                @csrf @if ($editing) @method('PUT') @endif
                <div class="form-grid">
                    <x-input name="name" label="Project name" :value="$project->name" required/>
                    <x-input name="url" label="Website URL" :value="$project->url" placeholder="example.com"/>
                    <x-select name="type" label="Type" :options="config('ledger.project_types')" :value="$project->type"/>
                    <x-select name="status" label="Status" :options="config('ledger.project_statuses')" :value="$project->status"/>
                    <x-select name="client_id" label="Client" :options="$clients->pluck('name', 'id')" :value="$project->client_id" placeholder="— None (own project) —"
                        :help="$clients->isEmpty() ? 'Add clients first under Clients.' : null"/>
                    <x-input name="started_on" label="Started on" type="date" :value="$project->started_on?->toDateString()"/>
                </div>

                <fieldset>
                    <legend>Client billing</legend>
                    <div class="form-grid">
                        <x-input name="build_fee" label="One-time build fee" type="number" step="0.01" min="0" :value="$project->build_fee" help="Agreed price for building the site (record the payment as income)."/>
                        <x-select name="billing_cycle" label="Recurring billing" :options="['none' => 'None'] + collect(config('ledger.billing_cycles'))->map->label->all()" :value="$project->billing_cycle"/>
                        <x-input name="billing_amount" label="Amount per cycle" type="number" step="0.01" min="0" :value="$project->billing_amount" help="What the client pays each cycle (hosting + maintenance)."/>
                        <x-input name="next_billing_date" label="Next bill date" type="date" :value="$project->next_billing_date?->toDateString()"/>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Ad revenue sharing</legend>
                    <div class="form-grid">
                        <x-select name="partner_id" label="Partner" :options="$partners->pluck('name', 'id')" :value="$project->partner_id" placeholder="— Not shared (all mine) —"/>
                        <x-input name="partner_share_percent" label="Partner's share of ad revenue (%)" type="number" step="0.01" min="0" max="100" :value="$project->partner_share_percent"
                            help="Applied automatically when you record ad revenue on this project."/>
                    </div>
                </fieldset>

                <div class="form-grid"><x-textarea name="notes" label="Notes" :value="$project->notes"/></div>

                <div class="form-actions">
                    @if ($editing)<x-delete-button :action="route('projects.destroy', $project)" confirm="Delete this project? Linked domains and transactions are kept but unlinked."/>@endif
                    <a class="btn" href="{{ $editing ? route('projects.show', $project) : route('projects.index') }}">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save project</button>
                </div>
            </form>
        </div>
    </div>
</x-layout>
