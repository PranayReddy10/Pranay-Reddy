<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the single owner login. Set OWNER_EMAIL / OWNER_PASSWORD in .env,
     * otherwise a random password is generated and printed once.
     */
    public function run(): void
    {
        $email = env('OWNER_EMAIL', 'owner@example.com');

        if (User::where('email', $email)->exists()) {
            $this->command?->info("Owner {$email} already exists — skipped.");

            return;
        }

        $password = env('OWNER_PASSWORD') ?: Str::password(16, symbols: false);

        User::create([
            'name' => env('OWNER_NAME', 'Owner'),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->command?->warn("Owner login: {$email}".(env('OWNER_PASSWORD') ? '' : " / {$password}  (save this now)"));
    }
}
