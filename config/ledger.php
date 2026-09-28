<?php

return [

    'currency' => env('LEDGER_CURRENCY', '₹'),

    // How many days ahead a renewal / payment shows up as "due soon".
    'due_soon_days' => (int) env('LEDGER_DUE_SOON_DAYS', 30),

    'billing_cycles' => [
        'monthly' => ['label' => 'Monthly', 'months' => 1],
        'quarterly' => ['label' => 'Quarterly', 'months' => 3],
        'half_yearly' => ['label' => 'Half-yearly', 'months' => 6],
        'yearly' => ['label' => 'Yearly', 'months' => 12],
    ],

    'income_categories' => [
        'client_payment' => 'Client payment (recurring)',
        'build_fee' => 'Website build fee',
        'ad_revenue' => 'Ad revenue',
        'marketing' => 'Marketing service',
        'domain_resale' => 'Domain / hosting resale',
        'other_income' => 'Other income',
    ],

    'expense_categories' => [
        'domain' => 'Domain purchase / renewal',
        'hosting' => 'Hosting / server',
        'software' => 'Software & tools',
        'ads_spend' => 'Ad spend / promotion',
        'freelancer' => 'Freelancer / outsourcing',
        'partner_payout' => 'Partner payout',
        'hardware' => 'Hardware',
        'internet' => 'Internet & phone',
        'other_expense' => 'Other expense',
    ],

    'account_types' => [
        'registrar' => 'Domain registrar',
        'hosting' => 'Hosting / cloud',
        'both' => 'Registrar + hosting',
        'ads' => 'Ad network',
        'payment' => 'Payment / bank',
        'other' => 'Other',
    ],

    'project_types' => [
        'client' => 'Client website',
        'own' => 'Own project',
        'ads' => 'Ads / content site',
    ],

    'project_statuses' => [
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'domain_statuses' => [
        'active' => 'Active',
        'expired' => 'Expired',
        'transferred' => 'Transferred',
        'dropped' => 'Dropped',
    ],

    'payment_methods' => ['UPI', 'Bank transfer', 'Card', 'Cash', 'PayPal', 'Wallet', 'Other'],

];
