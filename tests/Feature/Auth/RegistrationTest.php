<?php declare(strict_types = 1);

use App\Domain\Customer\Notifications\WelcomeNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('user can register with cpf and phone', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'cpf'                   => '529.982.247-25',
        'phone'                 => '(11) 99999-9999',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->cpf)->toBe('529.982.247-25');
    expect($user->phone)->toBe('(11) 99999-9999');
});

test('user can register without cpf and phone', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->cpf)->toBeNull();
    expect($user->phone)->toBeNull();
});

test('registration fails with invalid cpf', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'cpf'                   => '111.111.111-11',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['cpf']);
});

test('registration fails with duplicate cpf', function () {
    User::factory()->create(['cpf' => '529.982.247-25']);

    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'cpf'                   => '529.982.247-25',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['cpf']);
});

test('registration fails with invalid phone format', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'phone'                 => '11999999999',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['phone']);
});

test('registration fails with cpf in wrong format', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'cpf'                   => '52998224725',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['cpf']);
});

test('registration accepts phone with 8 digits', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'phone'                 => '(11) 9999-9999',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->phone)->toBe('(11) 9999-9999');
});

test('sends welcome notification after registration', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    Notification::assertSentTo($user, WelcomeNotification::class);
});

test('welcome notification is not sent when registration fails', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'invalid-email',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    Notification::assertNothingSent();
});
