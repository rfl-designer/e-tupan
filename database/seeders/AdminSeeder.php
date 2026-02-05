<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates the master admin account using environment variables.
     */
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'password');

        // Check if admin already exists
        if (Admin::where('email', $email)->exists()) {
            $this->command->info("Admin master já existe: {$email}");

            return;
        }

        Admin::create([
            'name'              => 'Administrador Master',
            'email'             => $email,
            'password'          => $password,
            'role'              => 'master',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info("Admin master criado: {$email}");
        $this->command->warn('IMPORTANTE: Configure o 2FA no primeiro acesso!');
    }
}
