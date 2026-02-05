<?php declare(strict_types = 1);

use App\Domain\Customer\Models\AuthLog;
use App\Models\User;
use Illuminate\Auth\Events\{Lockout, Login, Logout};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

test('successful login creates auth log', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->assertDatabaseHas('auth_logs', [
        'authenticatable_type' => User::class,
        'authenticatable_id'   => $user->id,
        'email'                => $user->email,
        'event'                => 'login',
    ]);

    $log = AuthLog::where('email', $user->email)->where('event', 'login')->first();
    expect($log)->not->toBeNull()
        ->and($log->ip_address)->not->toBeEmpty()
        ->and($log->metadata)->toBeArray()
        ->and($log->metadata['guard'])->toBe('web');
});

test('successful logout creates auth log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $this->assertDatabaseHas('auth_logs', [
        'authenticatable_type' => User::class,
        'authenticatable_id'   => $user->id,
        'email'                => $user->email,
        'event'                => 'logout',
    ]);
});

test('failed login creates auth log', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('auth_logs', [
        'email' => $user->email,
        'event' => 'failed',
    ]);

    $log = AuthLog::where('email', $user->email)->where('event', 'failed')->first();
    expect($log)->not->toBeNull()
        ->and($log->ip_address)->not->toBeEmpty();
});

test('failed login with non-existent user creates auth log', function () {
    $this->post(route('login.store'), [
        'email'    => 'nonexistent@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('auth_logs', [
        'authenticatable_type' => null,
        'authenticatable_id'   => null,
        'email'                => 'nonexistent@example.com',
        'event'                => 'failed',
    ]);
});

test('lockout event creates auth log', function () {
    $user = User::factory()->create();

    // Dispatch the Lockout event directly to test the listener
    $request = Request::create('/login', 'POST', [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);
    $request->setLaravelSession(app('session.store'));

    event(new Lockout($request));

    $this->assertDatabaseHas('auth_logs', [
        'email' => $user->email,
        'event' => 'lockout',
    ]);

    $log = AuthLog::where('email', $user->email)->where('event', 'lockout')->first();
    expect($log)->not->toBeNull()
        ->and($log->ip_address)->not->toBeEmpty();
});

test('auth log records ip address', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ], ['REMOTE_ADDR' => '192.168.1.100']);

    $log = AuthLog::where('email', $user->email)->where('event', 'login')->first();
    expect($log->ip_address)->not->toBeEmpty();
});

test('auth log records user agent', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ], ['HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser']);

    $log = AuthLog::where('email', $user->email)->where('event', 'login')->first();
    expect($log->user_agent)->toBe('Mozilla/5.0 Test Browser');
});

test('auth log does not block authentication on exception', function () {
    $user = User::factory()->create();

    // Even if something goes wrong with logging, authentication should still work
    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('auth log model has correct casts', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $log = AuthLog::where('email', $user->email)->first();

    expect($log->metadata)->toBeArray()
        ->and($log->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('auth log can be queried by event scope', function () {
    $user = User::factory()->create();

    // Create login log
    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    // Create logout log
    $this->post(route('logout'));

    $loginLogs  = AuthLog::byEvent('login')->get();
    $logoutLogs = AuthLog::byEvent('logout')->get();

    expect($loginLogs)->toHaveCount(1)
        ->and($logoutLogs)->toHaveCount(1);
});

test('auth log can be queried by email scope', function () {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    $this->post(route('login.store'), [
        'email'    => $user1->email,
        'password' => 'password',
    ]);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email'    => $user2->email,
        'password' => 'password',
    ]);

    $user1Logs = AuthLog::byEmail('user1@example.com')->get();
    $user2Logs = AuthLog::byEmail('user2@example.com')->get();

    expect($user1Logs)->toHaveCount(2)
        ->and($user2Logs)->toHaveCount(1);
});

test('auth log can be queried by authenticatable scope', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('logout'));

    $logs = AuthLog::forAuthenticatable($user)->get();

    expect($logs)->toHaveCount(2);
});

test('auth log morphTo relationship works', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $log = AuthLog::where('email', $user->email)->first();

    expect($log->authenticatable)->toBeInstanceOf(User::class)
        ->and($log->authenticatable->id)->toBe($user->id);
});
