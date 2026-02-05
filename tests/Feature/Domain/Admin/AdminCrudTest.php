<?php declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Notifications\AdminInvitation;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin list page is displayed for master', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->get(route('admin.administrators.index'));

    $response->assertStatus(200);
    $response->assertSee('Administradores');
});

test('operator cannot access admin list', function () {
    $operator = Admin::factory()->withTwoFactor()->create(['role' => 'operator']);

    $response = actingAsAdminWith2FA($this, $operator)
        ->get(route('admin.administrators.index'));

    $response->assertForbidden();
});

test('master can view create admin form', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->get(route('admin.administrators.create'));

    $response->assertStatus(200);
    $response->assertSee('Novo Administrador');
});

test('master can create admin', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    Notification::fake();

    $response = actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), [
            'name'  => 'New Admin',
            'email' => 'newadmin@example.com',
            'role'  => 'operator',
        ]);

    $response->assertRedirect(route('admin.administrators.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('admins', [
        'name'      => 'New Admin',
        'email'     => 'newadmin@example.com',
        'role'      => 'operator',
        'is_active' => true,
    ]);
});

test('master can create another master admin', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    Notification::fake();

    $response = actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), [
            'name'  => 'Another Master',
            'email' => 'master2@example.com',
            'role'  => 'master',
        ]);

    $response->assertRedirect(route('admin.administrators.index'));

    $this->assertDatabaseHas('admins', [
        'email' => 'master2@example.com',
        'role'  => 'master',
    ]);
});

test('master can view edit admin form', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->get(route('admin.administrators.edit', $admin));

    $response->assertStatus(200);
    $response->assertSee('Editar Administrador');
    $response->assertSee($admin->name);
});

test('master can edit admin', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->put(route('admin.administrators.update', $admin), [
            'name'      => 'Updated Name',
            'email'     => 'updated@example.com',
            'role'      => 'operator',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.administrators.index'));
    $response->assertSessionHas('success');

    $admin->refresh();
    expect($admin->name)->toBe('Updated Name')
        ->and($admin->email)->toBe('updated@example.com');
});

test('master can deactivate admin', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create(['is_active' => true]);

    $response = actingAsAdminWith2FA($this, $master)
        ->put(route('admin.administrators.update', $admin), [
            'name'      => $admin->name,
            'email'     => $admin->email,
            'role'      => 'operator',
            'is_active' => false,
        ]);

    $response->assertRedirect(route('admin.administrators.index'));

    $admin->refresh();
    expect($admin->is_active)->toBeFalse();
});

test('master can delete admin', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->delete(route('admin.administrators.destroy', $admin));

    $response->assertRedirect(route('admin.administrators.index'));
    $response->assertSessionHas('success');

    // Soft deleted
    $this->assertSoftDeleted('admins', ['id' => $admin->id]);
});

test('admin cannot delete self', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->delete(route('admin.administrators.destroy', $master));

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');

    // Not deleted
    $this->assertDatabaseHas('admins', [
        'id'         => $master->id,
        'deleted_at' => null,
    ]);
});

test('cannot delete last master', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    // Create an operator to ensure we have another admin
    Admin::factory()->create(['role' => 'operator']);

    $response = actingAsAdminWith2FA($this, $master)
        ->delete(route('admin.administrators.destroy', $master));

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');

    // Not deleted
    $this->assertDatabaseHas('admins', [
        'id'         => $master->id,
        'deleted_at' => null,
    ]);
});

test('can delete master when there are other masters', function () {
    $master1 = Admin::factory()->master()->withTwoFactor()->create();
    $master2 = Admin::factory()->master()->create();

    $response = actingAsAdminWith2FA($this, $master1)
        ->delete(route('admin.administrators.destroy', $master2));

    $response->assertRedirect(route('admin.administrators.index'));
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('admins', ['id' => $master2->id]);
});

test('cannot demote last master', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    Admin::factory()->create(['role' => 'operator']);

    // Try to demote the only master
    $response = actingAsAdminWith2FA($this, $master)
        ->put(route('admin.administrators.update', $master), [
            'name'      => $master->name,
            'email'     => $master->email,
            'role'      => 'operator',
            'is_active' => true,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('role');

    $master->refresh();
    expect($master->role)->toBe('master');
});

test('can demote master when there are other masters', function () {
    $master1 = Admin::factory()->master()->withTwoFactor()->create();
    $master2 = Admin::factory()->master()->create();

    $response = actingAsAdminWith2FA($this, $master1)
        ->put(route('admin.administrators.update', $master2), [
            'name'      => $master2->name,
            'email'     => $master2->email,
            'role'      => 'operator',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.administrators.index'));
    $response->assertSessionHas('success');

    $master2->refresh();
    expect($master2->role)->toBe('operator');
});

test('invitation email is sent on create', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    Notification::fake();

    actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), [
            'name'  => 'New Admin',
            'email' => 'newadmin@example.com',
            'role'  => 'operator',
        ]);

    $newAdmin = Admin::where('email', 'newadmin@example.com')->first();

    Notification::assertSentTo($newAdmin, AdminInvitation::class);
});

test('admin is soft deleted', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create();

    actingAsAdminWith2FA($this, $master)
        ->delete(route('admin.administrators.destroy', $admin));

    // Check soft delete
    expect(Admin::withTrashed()->find($admin->id))->not->toBeNull()
        ->and(Admin::withTrashed()->find($admin->id)->deleted_at)->not->toBeNull()
        ->and(Admin::find($admin->id))->toBeNull();
});

test('operator cannot create admin', function () {
    $operator = Admin::factory()->withTwoFactor()->create(['role' => 'operator']);

    $response = actingAsAdminWith2FA($this, $operator)
        ->post(route('admin.administrators.store'), [
            'name'  => 'New Admin',
            'email' => 'newadmin@example.com',
            'role'  => 'operator',
        ]);

    $response->assertForbidden();
});

test('operator cannot edit admin', function () {
    $operator = Admin::factory()->withTwoFactor()->create(['role' => 'operator']);
    $admin    = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $operator)
        ->put(route('admin.administrators.update', $admin), [
            'name'      => 'Updated Name',
            'email'     => 'updated@example.com',
            'role'      => 'operator',
            'is_active' => true,
        ]);

    $response->assertForbidden();
});

test('operator cannot delete admin', function () {
    $operator = Admin::factory()->withTwoFactor()->create(['role' => 'operator']);
    $admin    = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $operator)
        ->delete(route('admin.administrators.destroy', $admin));

    $response->assertForbidden();
});

test('create admin validates required fields', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), []);

    $response->assertSessionHasErrors(['name', 'email', 'role']);
});

test('create admin validates email format', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), [
            'name'  => 'Test Admin',
            'email' => 'invalid-email',
            'role'  => 'operator',
        ]);

    $response->assertSessionHasErrors('email');
});

test('create admin validates unique email', function () {
    $master        = Admin::factory()->master()->withTwoFactor()->create();
    $existingAdmin = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), [
            'name'  => 'Test Admin',
            'email' => $existingAdmin->email,
            'role'  => 'operator',
        ]);

    $response->assertSessionHasErrors('email');
});

test('create admin validates role values', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->post(route('admin.administrators.store'), [
            'name'  => 'Test Admin',
            'email' => 'test@example.com',
            'role'  => 'invalid-role',
        ]);

    $response->assertSessionHasErrors('role');
});

test('update admin validates required fields', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->put(route('admin.administrators.update', $admin), []);

    $response->assertSessionHasErrors(['name', 'email', 'role', 'is_active']);
});

test('update admin allows same email for same admin', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin  = Admin::factory()->create();

    $response = actingAsAdminWith2FA($this, $master)
        ->put(route('admin.administrators.update', $admin), [
            'name'      => 'Updated Name',
            'email'     => $admin->email, // Same email
            'role'      => 'operator',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.administrators.index'));
    $response->assertSessionHasNoErrors();
});

test('admin list shows all admins', function () {
    $master = Admin::factory()->master()->withTwoFactor()->create();
    $admin1 = Admin::factory()->create(['name' => 'Admin One']);
    $admin2 = Admin::factory()->create(['name' => 'Admin Two']);

    $response = actingAsAdminWith2FA($this, $master)
        ->get(route('admin.administrators.index'));

    $response->assertSee($master->name);
    $response->assertSee($admin1->name);
    $response->assertSee($admin2->name);
});

test('unauthenticated user cannot access admin crud', function () {
    $response = $this->get(route('admin.administrators.index'));

    $response->assertRedirect(route('admin.login'));
});
