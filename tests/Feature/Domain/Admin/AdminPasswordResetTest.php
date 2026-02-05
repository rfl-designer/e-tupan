<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Notifications\AdminResetPasswordNotification;
use Illuminate\Support\Facades\{Hash, Notification, Password};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin forgot password page is displayed', function () {
    $response = $this->get(route('admin.password.request'));

    $response->assertStatus(200);
    $response->assertSee('Admin Password Recovery');
});

test('admin can request password reset link', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $response = $this->post(route('admin.password.email'), [
        'email' => $admin->email,
    ]);

    $response->assertSessionHas('status');

    Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
});

test('admin reset password page is displayed with valid token', function () {
    $admin = Admin::factory()->create();

    $token = Password::broker('admins')->createToken($admin);

    $response = $this->get(route('admin.password.reset', [
        'token' => $token,
        'email' => $admin->email,
    ]));

    $response->assertStatus(200);
    $response->assertSee('Reset Admin Password');
});

test('admin can reset password with valid token', function () {
    $admin = Admin::factory()->create();

    $token = Password::broker('admins')->createToken($admin);

    $response = $this->post(route('admin.password.update'), [
        'token'                 => $token,
        'email'                 => $admin->email,
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('admin.login'));
    $response->assertSessionHas('status');

    $admin->refresh();

    expect(Hash::check('new-password', $admin->password))->toBeTrue();
});

test('admin cannot reset password with invalid token', function () {
    $admin = Admin::factory()->create();

    $response = $this->post(route('admin.password.update'), [
        'token'                 => 'invalid-token',
        'email'                 => $admin->email,
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('admin cannot reset password with expired token', function () {
    $admin = Admin::factory()->create();

    $token = Password::broker('admins')->createToken($admin);

    // Travel 31 minutes into the future (token expires in 30 minutes)
    $this->travel(31)->minutes();

    $response = $this->post(route('admin.password.update'), [
        'token'                 => $token,
        'email'                 => $admin->email,
        'password'              => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('admin password reset notification is sent', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $this->post(route('admin.password.email'), [
        'email' => $admin->email,
    ]);

    Notification::assertSentTo($admin, AdminResetPasswordNotification::class, function ($notification) {
        return !empty($notification->token);
    });
});

test('admin password reset prevents email enumeration', function () {
    Notification::fake();

    // Request reset for non-existent email
    $response = $this->post(route('admin.password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    // Should still show success message
    $response->assertSessionHas('status');

    // But no notification should be sent
    Notification::assertNothingSent();
});

test('admin password reset validates email is required', function () {
    $response = $this->post(route('admin.password.email'), [
        'email' => '',
    ]);

    $response->assertSessionHasErrors('email');
});

test('admin password reset validates email format', function () {
    $response = $this->post(route('admin.password.email'), [
        'email' => 'invalid-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('admin password reset validates password is required', function () {
    $admin = Admin::factory()->create();
    $token = Password::broker('admins')->createToken($admin);

    $response = $this->post(route('admin.password.update'), [
        'token'                 => $token,
        'email'                 => $admin->email,
        'password'              => '',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');
});

test('admin password reset validates password confirmation', function () {
    $admin = Admin::factory()->create();
    $token = Password::broker('admins')->createToken($admin);

    $response = $this->post(route('admin.password.update'), [
        'token'                 => $token,
        'email'                 => $admin->email,
        'password'              => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
});

test('admin login page has forgot password link', function () {
    $response = $this->get(route('admin.login'));

    $response->assertStatus(200);
    $response->assertSee('Forgot password?');
    $response->assertSee(route('admin.password.request'));
});

test('admin password reset notification contains correct url', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $this->post(route('admin.password.email'), [
        'email' => $admin->email,
    ]);

    Notification::assertSentTo($admin, AdminResetPasswordNotification::class, function ($notification) use ($admin) {
        $mailMessage = $notification->toMail($admin);

        // Check that the action URL contains the correct route
        $actionUrl = $mailMessage->actionUrl;

        return str_contains($actionUrl, 'admin/reset-password')
            && str_contains($actionUrl, $notification->token)
            && str_contains($actionUrl, urlencode($admin->email));
    });
});

test('admin password reset notification has correct subject', function () {
    Notification::fake();

    $admin = Admin::factory()->create();

    $this->post(route('admin.password.email'), [
        'email' => $admin->email,
    ]);

    Notification::assertSentTo($admin, AdminResetPasswordNotification::class, function ($notification) use ($admin) {
        $mailMessage = $notification->toMail($admin);

        return $mailMessage->subject === 'Redefinição de Senha - Painel Administrativo';
    });
});

test('authenticated admin is redirected from forgot password page', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.password.request'));

    $response->assertRedirect(route('admin.dashboard'));
});

test('authenticated admin is redirected from reset password page', function () {
    $admin = Admin::factory()->create();
    $token = Password::broker('admins')->createToken($admin);

    $response = $this->actingAs($admin, 'admin')
        ->get(route('admin.password.reset', ['token' => $token]));

    $response->assertRedirect(route('admin.dashboard'));
});
