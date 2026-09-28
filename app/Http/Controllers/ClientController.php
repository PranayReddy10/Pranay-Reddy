<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::withCount('projects')
            ->withSum(['transactions as paid_total' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->when($request->input('q'), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$term%")->orWhere('company', 'like', "%$term%")))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request));

        return redirect()->route('clients.show', $client)->with('status', 'Client added.');
    }

    public function show(Client $client): View
    {
        $client->load(['projects.domains', 'transactions' => fn ($q) => $q->with('project')->latest('date')->limit(50)]);

        return view('clients.show', [
            'client' => $client,
            'totalPaid' => (float) $client->transactions()->where('type', 'income')->sum('amount'),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));

        return redirect()->route('clients.show', $client)->with('status', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Client deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
