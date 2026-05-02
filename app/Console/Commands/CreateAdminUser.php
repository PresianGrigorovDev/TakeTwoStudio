<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature   = 'admin:create {email} {password} {--name=Admin}';
    protected $description = 'Create or update an admin user';

    public function handle(): int
    {
        $email    = $this->argument('email');
        $password = $this->argument('password');
        $name     = $this->option('name');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($password),
            ]
        );

        $action = $user->wasRecentlyCreated ? 'Създаден' : 'Обновен';
        $this->info("{$action} потребител: {$email}");
        $this->line("Парола: {$password}");
        $this->line("Влез на: http://localhost:8000/admin");

        return self::SUCCESS;
    }
}
