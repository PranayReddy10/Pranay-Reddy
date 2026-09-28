<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['profile' => Setting::allValues()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(config('ledger.profile_fields'))->map(fn () => ['nullable', 'string', 'max:2000'])->all();
        $rules['email'] = ['nullable', 'email', 'max:255'];
        $rules['invoice_prefix'] = ['nullable', 'alpha_dash', 'max:12'];

        Setting::put($request->validate($rules));

        return back()->with('status', 'Profile saved. New bills will use these details.');
    }
}
