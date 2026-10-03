<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('portfolio:admin {--email= : Login email} {--password= : Login password (asked for if omitted)}')]
#[Description('Create the admin login for /admin, or reset its password')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?: text('Email for the admin login', default: config('portfolio.profile.email'), required: true);
        $password = $this->option('password') ?: password('Password (at least 10 characters)', required: true);

        $validator = Validator::make(compact('email', 'password'), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => config('portfolio.profile.name'),
            'password' => $password,
        ]);

        $this->info(($user->wasRecentlyCreated ? 'Admin created' : 'Password updated')." for {$email}. Sign in at ".route('admin.login'));

        return self::SUCCESS;
    }
}
