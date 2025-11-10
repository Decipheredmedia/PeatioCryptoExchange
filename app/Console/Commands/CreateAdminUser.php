<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email} {password}';
    protected $description = 'Create an admin user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'sn' => 'SN' . strtoupper(uniqid()),
            'activated' => true,
        ]);

        $user->assignRole('admin');

        $this->info("Admin user created successfully!");
        $this->info("Email: {$email}");
        $this->info("SN: {$user->sn}");

        return 0;
    }
}
