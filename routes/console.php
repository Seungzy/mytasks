<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('membership:admin {email} {--name=Administrator}', function () {
    $email = $this->argument('email');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Enter a valid email address.');

        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('This email already exists. No account was changed.');

        return 1;
    }
    $password = Str::password(18);
    $user = new User(['name' => $this->option('name'), 'email' => $email, 'password' => $password]);
    $user->is_admin = true;
    $user->save();
    $this->info('Administrator created: '.$email);
    $this->line('Temporary password: '.$password);
})->purpose('Create an administrator with a randomly generated password');
