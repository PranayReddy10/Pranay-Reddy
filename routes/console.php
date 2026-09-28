<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('ledger:owner {email?}', function (?string $email = null) {
    $email ??= $this->ask('Email');
    $name = $this->ask('Name', User::where('email', $email)->value('name') ?? 'Owner');
    $password = $this->secret('New password (min 8 chars)');

    if (strlen((string) $password) < 8) {
        $this->error('Password must be at least 8 characters.');

        return 1;
    }

    User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make($password)]);
    $this->info("Login ready for {$email}.");
})->purpose('Create the owner login or reset its password');
