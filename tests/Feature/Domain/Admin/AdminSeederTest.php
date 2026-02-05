<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin seeder creates master admin', function () {
    // Run the seeder
    $this->seed(AdminSeeder::class);

    $admin = Admin::where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Administrador Master')
        ->and($admin->role)->toBe('master')
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull();
});

test('admin seeder does not duplicate admin', function () {
    // Create admin first
    Admin::create([
        'name'      => 'Existing Admin',
        'email'     => 'admin@example.com',
        'password'  => 'existing-password',
        'role'      => 'master',
        'is_active' => true,
    ]);

    // Run the seeder
    $this->seed(AdminSeeder::class);

    // Should still have only one admin with this email
    $count = Admin::where('email', 'admin@example.com')->count();

    expect($count)->toBe(1);

    // Original admin should be unchanged
    $admin = Admin::where('email', 'admin@example.com')->first();
    expect($admin->name)->toBe('Existing Admin');
});

test('admin seeder creates admin with hashed password', function () {
    $this->seed(AdminSeeder::class);

    $admin = Admin::where('email', 'admin@example.com')->first();

    // Password should be hashed, not plain text
    expect($admin->password)->not->toBe('password')
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('admin seeder creates admin that can authenticate', function () {
    $this->seed(AdminSeeder::class);

    $response = $this->post(route('admin.login.store'), [
        'email'    => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs(Admin::where('email', 'admin@example.com')->first(), 'admin');
});

test('admin seeder creates admin with master role', function () {
    $this->seed(AdminSeeder::class);

    $admin = Admin::where('email', 'admin@example.com')->first();

    expect($admin->isMaster())->toBeTrue();
});
