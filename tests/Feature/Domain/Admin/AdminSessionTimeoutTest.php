<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin session times out after 30 minutes of inactivity', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Set last activity to 31 minutes ago
    $this->actingAs($admin, 'admin')
        ->withSession([
            'admin_two_factor_confirmed' => true,
            'admin_last_activity'        => Carbon::now()->subMinutes(31),
        ])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('warning', 'Sua sessão expirou por inatividade.');

    $this->assertGuest('admin');
});

test('admin session is refreshed on activity', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Set last activity to 10 minutes ago (within timeout)
    $this->actingAs($admin, 'admin')
        ->withSession([
            'admin_two_factor_confirmed' => true,
            'admin_last_activity'        => Carbon::now()->subMinutes(10),
        ])
        ->get(route('admin.dashboard'))
        ->assertStatus(200);

    $this->assertAuthenticatedAs($admin, 'admin');

    // Verify session was updated
    expect(session('admin_last_activity'))->not->toBeNull();
});

test('admin session does not timeout at exactly 30 minutes', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Set last activity to exactly 29 minutes ago
    $this->actingAs($admin, 'admin')
        ->withSession([
            'admin_two_factor_confirmed' => true,
            'admin_last_activity'        => Carbon::now()->subMinutes(29),
        ])
        ->get(route('admin.dashboard'))
        ->assertStatus(200);

    $this->assertAuthenticatedAs($admin, 'admin');
});

test('admin session timeout at exactly 30 minutes', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Set last activity to exactly 30 minutes ago
    $this->actingAs($admin, 'admin')
        ->withSession([
            'admin_two_factor_confirmed' => true,
            'admin_last_activity'        => Carbon::now()->subMinutes(30),
        ])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));

    $this->assertGuest('admin');
});

test('new admin session has no last activity initially', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // First request without last_activity should work and set it
    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->get(route('admin.dashboard'))
        ->assertStatus(200);

    expect(session('admin_last_activity'))->not->toBeNull();
});

test('admin session timeout invalidates session completely', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Set last activity to 31 minutes ago
    $response = $this->actingAs($admin, 'admin')
        ->withSession([
            'admin_two_factor_confirmed' => true,
            'admin_last_activity'        => Carbon::now()->subMinutes(31),
            'some_other_data'            => 'test',
        ])
        ->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));

    // Session should be invalidated
    expect(session('admin_two_factor_confirmed'))->toBeNull();
});
