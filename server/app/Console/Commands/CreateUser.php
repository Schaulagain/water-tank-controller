<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:create {email}')]
#[Description('Create the login for the page, or change its password')]
class CreateUser extends Command
{
    public function handle(): int
    {
        $password = $this->secret('Password');

        if (strlen((string) $password) < 8) {
            $this->error('Use at least 8 characters.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => 'Owner', 'password' => $password],
        );

        $this->info('Saved. You can log in now.');

        return self::SUCCESS;
    }
}
