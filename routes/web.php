<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PersonalExpenseController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RenewalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::view('/offline', 'offline')->name('offline');

// Client-facing bill, shared by secret link (no login).
Route::get('/bill/{token}', [InvoiceController::class, 'public'])->name('bills.public')->middleware('throttle:60,1');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/renewals', RenewalController::class)->name('renewals');
    Route::get('/reports', ReportController::class)->name('reports');

    Route::resource('clients', ClientController::class);
    Route::resource('partners', PartnerController::class);
    Route::resource('accounts', AccountController::class);
    Route::resource('servers', ServerController::class);
    Route::resource('domains', DomainController::class);
    Route::resource('projects', ProjectController::class);
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::resource('transactions', TransactionController::class)->except('show');

    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::post('/invoices/{invoice}/link', [InvoiceController::class, 'link'])->name('invoices.link');
    Route::delete('/invoices/{invoice}/payments/{transaction}', [InvoiceController::class, 'unlink'])->name('invoices.unlink');
    Route::post('/invoices/{invoice}/regenerate-link', [InvoiceController::class, 'regenerateLink'])->name('invoices.regenerate');

    Route::post('/personal/budgets', [PersonalExpenseController::class, 'budgets'])->name('personal.budgets');
    Route::resource('personal', PersonalExpenseController::class)->except('show');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update']);

    Route::post('/projects/{project}/collect', [ProjectController::class, 'collect'])->name('projects.collect');
    Route::post('/domains/{domain}/renew', [DomainController::class, 'renew'])->name('domains.renew');
    Route::post('/servers/{server}/pay', [ServerController::class, 'pay'])->name('servers.pay');
    Route::post('/partners/{partner}/payout', [PartnerController::class, 'payout'])->name('partners.payout');
});
