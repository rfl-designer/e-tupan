<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use PragmaRX\Google2FA\Google2FA;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin without 2fa is redirected to setup', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.two-factor.setup'));
});

test('admin can view 2fa setup page', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.two-factor.setup'));

    $response->assertStatus(200);
    $response->assertSee('Two-Factor Authentication Setup');
    $response->assertSee('Recovery Codes');
});

test('admin 2fa setup page shows qr code and recovery codes', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.two-factor.setup'));

    $response->assertStatus(200);

    // Refresh admin to get the generated 2FA secret
    $admin->refresh();

    expect($admin->two_factor_secret)->not->toBeNull()
        ->and($admin->two_factor_recovery_codes)->not->toBeNull();
});

test('admin can confirm 2fa with valid code', function () {
    $admin = Admin::factory()->create();

    // First, visit setup to generate 2FA secret
    $this->actingAs($admin, 'admin')
        ->get(route('admin.two-factor.setup'));

    $admin->refresh();

    // Generate valid TOTP code
    $google2fa = new Google2FA();
    $validCode = $google2fa->getCurrentOtp(decrypt($admin->two_factor_secret));

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.two-factor.confirm'), [
            'code' => $validCode,
        ]);

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertSessionHas('success');

    $admin->refresh();
    expect($admin->two_factor_confirmed_at)->not->toBeNull();
});

test('admin cannot confirm 2fa with invalid code', function () {
    $admin = Admin::factory()->create();

    // First, visit setup to generate 2FA secret
    $this->actingAs($admin, 'admin')
        ->get(route('admin.two-factor.setup'));

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.two-factor.confirm'), [
            'code' => '000000',
        ]);

    $response->assertSessionHasErrors('code');

    $admin->refresh();
    expect($admin->two_factor_confirmed_at)->toBeNull();
});

test('admin with 2fa configured is redirected to challenge after login', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    // Try to access dashboard
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.two-factor.challenge'));
});

test('admin can view 2fa challenge page', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response = $this->get(route('admin.two-factor.challenge'));

    $response->assertStatus(200);
    $response->assertSee('Two-Factor Authentication');
    $response->assertSee('Authentication Code');
});

test('admin can pass 2fa challenge with valid code', function () {
    // Create admin with real 2FA secret
    $google2fa = new Google2FA();
    $secret    = $google2fa->generateSecretKey();

    $admin = Admin::factory()->create([
        'two_factor_secret'         => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
        'two_factor_confirmed_at'   => now(),
    ]);

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    // Generate valid TOTP code
    $validCode = $google2fa->getCurrentOtp($secret);

    $response = $this->post(route('admin.two-factor.verify'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(session('admin_two_factor_confirmed'))->toBeTrue();
});

test('admin cannot pass 2fa challenge with invalid code', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('admin.two-factor.verify'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');
    expect(session('admin_two_factor_confirmed'))->toBeNull();
});

test('admin can pass 2fa challenge with recovery code', function () {
    $recoveryCodes = ['recovery-code-1', 'recovery-code-2', 'recovery-code-3'];

    $admin = Admin::factory()->create([
        'two_factor_secret'         => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        'two_factor_confirmed_at'   => now(),
    ]);

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('admin.two-factor.verify'), [
        'recovery_code' => 'recovery-code-1',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect(session('admin_two_factor_confirmed'))->toBeTrue();
});

test('admin cannot pass 2fa challenge with invalid recovery code', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('admin.two-factor.verify'), [
        'recovery_code' => 'invalid-recovery-code',
    ]);

    $response->assertSessionHasErrors('recovery_code');
    expect(session('admin_two_factor_confirmed'))->toBeNull();
});

test('recovery code is removed after use', function () {
    $recoveryCodes = ['recovery-code-1', 'recovery-code-2', 'recovery-code-3'];

    $admin = Admin::factory()->create([
        'two_factor_secret'         => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        'two_factor_confirmed_at'   => now(),
    ]);

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $this->post(route('admin.two-factor.verify'), [
        'recovery_code' => 'recovery-code-1',
    ]);

    $admin->refresh();
    $remainingCodes = json_decode(decrypt($admin->two_factor_recovery_codes), true);

    expect($remainingCodes)->toHaveCount(2)
        ->and($remainingCodes)->not->toContain('recovery-code-1')
        ->and($remainingCodes)->toContain('recovery-code-2')
        ->and($remainingCodes)->toContain('recovery-code-3');
});

test('admin with confirmed 2fa can access dashboard after challenge', function () {
    // Create admin with real 2FA secret
    $google2fa = new Google2FA();
    $secret    = $google2fa->generateSecretKey();

    $admin = Admin::factory()->create([
        'two_factor_secret'         => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at'   => now(),
    ]);

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    // Pass 2FA challenge
    $validCode = $google2fa->getCurrentOtp($secret);
    $this->post(route('admin.two-factor.verify'), [
        'code' => $validCode,
    ]);

    // Now should be able to access dashboard
    $response = $this->get(route('admin.dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});

test('admin with already confirmed 2fa is redirected from setup page', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Set session as 2FA confirmed
    $response = $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->get(route('admin.two-factor.setup'));

    $response->assertRedirect(route('admin.dashboard'));
});

test('2fa challenge requires code or recovery code', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Login
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('admin.two-factor.verify'), []);

    $response->assertSessionHasErrors('code');
});

test('admin can logout even with 2fa configured', function () {
    // Create admin with real 2FA secret
    $google2fa = new Google2FA();
    $secret    = $google2fa->generateSecretKey();

    $admin = Admin::factory()->create([
        'two_factor_secret'         => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at'   => now(),
    ]);

    // Login and pass 2FA
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $validCode = $google2fa->getCurrentOtp($secret);
    $this->post(route('admin.two-factor.verify'), [
        'code' => $validCode,
    ]);

    // Logout
    $response = $this->post(route('admin.logout'));

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest('admin');
});

test('2fa session is cleared on logout', function () {
    // Create admin with real 2FA secret
    $google2fa = new Google2FA();
    $secret    = $google2fa->generateSecretKey();

    $admin = Admin::factory()->create([
        'two_factor_secret'         => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at'   => now(),
    ]);

    // Login and pass 2FA
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $validCode = $google2fa->getCurrentOtp($secret);
    $this->post(route('admin.two-factor.verify'), [
        'code' => $validCode,
    ]);

    // Logout
    $this->post(route('admin.logout'));

    // Login again
    $this->post(route('admin.login.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    // Should be redirected to challenge again
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('admin.two-factor.challenge'));
});

test('middleware redirects unauthenticated admin to login', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});
