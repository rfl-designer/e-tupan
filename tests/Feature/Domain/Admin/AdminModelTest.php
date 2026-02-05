<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\{Auth, Hash};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin model exists', function () {
    expect(class_exists(Admin::class))->toBeTrue();
});

test('admin has correct fillable attributes', function () {
    $admin = new Admin();

    expect($admin->getFillable())->toBe([
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ]);
});

test('admin has correct hidden attributes', function () {
    $admin = new Admin();

    expect($admin->getHidden())->toBe([
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ]);
});

test('admin has correct casts', function () {
    $admin = new Admin();
    $casts = $admin->getCasts();

    expect($casts)->toHaveKey('email_verified_at', 'datetime')
        ->toHaveKey('password', 'hashed')
        ->toHaveKey('is_active', 'boolean')
        ->toHaveKey('two_factor_confirmed_at', 'datetime')
        ->toHaveKey('last_login_at', 'datetime');
});

test('admin is_master method returns true for master role', function () {
    $admin = Admin::factory()->master()->make();

    expect($admin->isMaster())->toBeTrue()
        ->and($admin->isOperator())->toBeFalse();
});

test('admin is_operator method returns true for operator role', function () {
    $admin = Admin::factory()->make();

    expect($admin->isOperator())->toBeTrue()
        ->and($admin->isMaster())->toBeFalse();
});

test('admin guard is configured', function () {
    $guards = config('auth.guards');

    expect($guards)->toHaveKey('admin')
        ->and($guards['admin'])->toBe([
            'driver'   => 'session',
            'provider' => 'admins',
        ]);
});

test('admin provider is configured', function () {
    $providers = config('auth.providers');

    expect($providers)->toHaveKey('admins')
        ->and($providers['admins'])->toBe([
            'driver' => 'eloquent',
            'model'  => Admin::class,
        ]);
});

test('admin password broker is configured with 30 minute expiry', function () {
    $passwords = config('auth.passwords');

    expect($passwords)->toHaveKey('admins')
        ->and($passwords['admins'])->toBe([
            'provider' => 'admins',
            'table'    => 'admin_password_reset_tokens',
            'expire'   => 30,
            'throttle' => 60,
        ]);
});

test('admin cannot authenticate via web guard', function () {
    $admin = Admin::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $authenticated = Auth::guard('web')->attempt([
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    expect($authenticated)->toBeFalse();
});

test('user cannot authenticate via admin guard', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $authenticated = Auth::guard('admin')->attempt([
        'email'    => $user->email,
        'password' => 'password',
    ]);

    expect($authenticated)->toBeFalse();
});

test('admin can authenticate via admin guard', function () {
    $admin = Admin::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $authenticated = Auth::guard('admin')->attempt([
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    expect($authenticated)->toBeTrue();
});

test('admin soft deletes', function () {
    $admin = Admin::factory()->create();

    $admin->delete();

    expect($admin->trashed())->toBeTrue()
        ->and(Admin::withTrashed()->find($admin->id))->not->toBeNull()
        ->and(Admin::find($admin->id))->toBeNull();
});

test('admin factory creates valid admin', function () {
    $admin = Admin::factory()->create();

    expect($admin)->toBeInstanceOf(Admin::class)
        ->and($admin->name)->not->toBeEmpty()
        ->and($admin->email)->not->toBeEmpty()
        ->and($admin->role)->toBe('operator')
        ->and($admin->is_active)->toBeTrue();
});

test('admin factory master state creates master admin', function () {
    $admin = Admin::factory()->master()->create();

    expect($admin->role)->toBe('master')
        ->and($admin->isMaster())->toBeTrue();
});

test('admin factory inactive state creates inactive admin', function () {
    $admin = Admin::factory()->inactive()->create();

    expect($admin->is_active)->toBeFalse();
});

test('admin initials method returns correct initials', function () {
    $admin = Admin::factory()->make(['name' => 'John Doe']);

    expect($admin->initials())->toBe('JD');
});

test('admin initials method handles single name', function () {
    $admin = Admin::factory()->make(['name' => 'Admin']);

    expect($admin->initials())->toBe('A');
});
