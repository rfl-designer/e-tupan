<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Customer\Models\AuthLog;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin POST actions are logged', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->post(route('admin.logout'));

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $admin->id)
        ->where('event', 'admin_action')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->email)->toBe($admin->email)
        ->and($log->metadata['method'])->toBe('POST')
        ->and($log->metadata['route'])->toBe('admin.logout');
});

test('admin GET actions are not logged', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    // Clear any existing logs
    AuthLog::query()->delete();

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->get(route('admin.dashboard'));

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $admin->id)
        ->where('event', 'admin_action')
        ->first();

    expect($log)->toBeNull();
});

test('admin DELETE actions are logged', function () {
    $masterAdmin   = Admin::factory()->master()->withTwoFactor()->create();
    $operatorAdmin = Admin::factory()->create();

    $this->actingAs($masterAdmin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->delete(route('admin.administrators.destroy', $operatorAdmin));

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $masterAdmin->id)
        ->where('event', 'admin_action')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata['method'])->toBe('DELETE');
});

test('admin PUT actions are logged', function () {
    $masterAdmin   = Admin::factory()->master()->withTwoFactor()->create();
    $operatorAdmin = Admin::factory()->create();

    $this->actingAs($masterAdmin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->put(route('admin.administrators.update', $operatorAdmin), [
            'name'      => 'Updated Name',
            'email'     => $operatorAdmin->email,
            'role'      => 'operator',
            'is_active' => true,
        ]);

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $masterAdmin->id)
        ->where('event', 'admin_action')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata['method'])->toBe('PUT');
});

test('admin action log includes ip address and user agent', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->post(route('admin.logout'));

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $admin->id)
        ->where('event', 'admin_action')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->ip_address)->not->toBeNull()
        ->and($log->user_agent)->not->toBeNull();
});

test('admin action log includes path information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $this->actingAs($admin, 'admin')
        ->withSession(['admin_two_factor_confirmed' => true])
        ->post(route('admin.logout'));

    $log = AuthLog::query()
        ->where('authenticatable_type', Admin::class)
        ->where('authenticatable_id', $admin->id)
        ->where('event', 'admin_action')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata['path'])->toBe('admin/logout');
});
