<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Customer\Models\AuthLog;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin login page is displayed', function () {
    $response = $this->get(route('admin.login'));

    $response->assertStatus(200);
    $response->assertSee('Admin Panel');
});

test('admin can login with valid credentials', function () {
    // Admin without 2FA will be redirected to setup
    $admin = Admin::factory()->create();

    $response = $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

test('admin cannot login with invalid credentials', function () {
    $admin = Admin::factory()->create();

    $response = $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('admin cannot login when inactive', function () {
    $admin = Admin::factory()->inactive()->create();

    $response = $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('admin can logout', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));

    $this->assertGuest('admin');
});

test('admin is redirected to login when not authenticated', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('admin login is rate limited', function () {
    $admin = Admin::factory()->create();

    // Make 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('admin.login.store'), [
            'email'    => $admin->email,
            'password' => 'wrong-password',
        ]);
    }

    // 6th attempt should be rate limited
    $response = $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});

test('admin login updates last login info', function () {
    $admin = Admin::factory()->create([
        'last_login_at' => null,
        'last_login_ip' => null,
    ]);

    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $admin->refresh();

    expect($admin->last_login_at)->not->toBeNull()
        ->and($admin->last_login_ip)->not->toBeNull();
});

test('admin login creates auth log', function () {
    $admin = Admin::factory()->create();

    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $admin->id)
        ->where('event', 'login')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->email)->toBe($admin->email)
        ->and($log->metadata['guard'])->toBe('admin');
});

test('admin session is independent from user session', function () {
    $admin = Admin::factory()->create();
    $user  = User::factory()->create();

    // Login as admin
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'admin');

    // Login as user in a separate request
    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    // Both should be authenticated in their respective guards
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertAuthenticatedAs($user, 'web');
});

test('authenticated admin is redirected from login page', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.login'));

    $response->assertRedirect(route('admin.dashboard'));
});

test('admin login validates email is required', function () {
    $response = $this->post(route('admin.login.store'), [
        'email'    => '',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('admin login validates password is required', function () {
    $response = $this->post(route('admin.login.store'), [
        'email'    => 'admin@example.com',
        'password' => '',
    ]);

    $response->assertSessionHasErrors('password');
});

test('admin login validates email format', function () {
    $response = $this->post(route('admin.login.store'), [
        'email'    => 'invalid-email',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('admin logout creates auth log', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->post(route('admin.logout'));

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $admin->id)
        ->where('event', 'logout')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->email)->toBe($admin->email);
});

test('user cannot access admin routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')
        ->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('admin can access dashboard after login', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->get(route('admin.dashboard'))
        ->assertStatus(200)
        ->assertSee('Dashboard');
});

test('admin remember me functionality works', function () {
    $admin = Admin::factory()->create();

    $response = $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertSessionHasNoErrors();

    // Check that remember token was set
    $admin->refresh();
    expect($admin->remember_token)->not->toBeNull();
});
