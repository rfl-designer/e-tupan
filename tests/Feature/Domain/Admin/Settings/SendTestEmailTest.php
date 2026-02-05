<?php

declare(strict_types = 1);

use App\Domain\Admin\Jobs\SendTestEmailJob;
use App\Domain\Admin\Livewire\Settings\EmailSettings;
use App\Domain\Admin\Mail\TestEmail;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Services\SettingsService;
use Illuminate\Support\Facades\{Mail, Queue};
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('EmailSettings shows send test email button', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->assertSee('Enviar email de teste');
});

test('EmailSettings opens test email modal when clicking button', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->assertSet('showTestEmailModal', false)
        ->call('openTestEmailModal')
        ->assertSet('showTestEmailModal', true);
});

test('EmailSettings test email modal has email input field', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->call('openTestEmailModal')
        ->assertSee('Email de destino');
});

test('EmailSettings validates test email address is required', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('testEmail', '')
        ->call('sendTestEmail')
        ->assertHasErrors(['testEmail' => 'required']);
});

test('EmailSettings validates test email address format', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('testEmail', 'invalid-email')
        ->call('sendTestEmail')
        ->assertHasErrors(['testEmail' => 'email']);
});

test('EmailSettings dispatches job when sending test email', function () {
    Queue::fake();

    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('email', [
        'sender_name'  => 'Test Store',
        'sender_email' => 'store@test.com',
        'provider'     => 'smtp',
        'smtp_host'    => 'smtp.test.com',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('testEmail', 'recipient@test.com')
        ->call('sendTestEmail');

    Queue::assertPushed(SendTestEmailJob::class, function ($job) {
        return $job->email === 'recipient@test.com';
    });
});

test('EmailSettings closes modal and shows success message after sending', function () {
    Queue::fake();

    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('email', [
        'sender_name'  => 'Test Store',
        'sender_email' => 'store@test.com',
        'provider'     => 'smtp',
        'smtp_host'    => 'smtp.test.com',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->call('openTestEmailModal')
        ->set('testEmail', 'recipient@test.com')
        ->call('sendTestEmail')
        ->assertSet('showTestEmailModal', false)
        ->assertDispatched('test-email-sent');
});

test('EmailSettings clears test email input after sending', function () {
    Queue::fake();

    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('email', [
        'sender_name'  => 'Test Store',
        'sender_email' => 'store@test.com',
        'provider'     => 'smtp',
        'smtp_host'    => 'smtp.test.com',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('testEmail', 'recipient@test.com')
        ->call('sendTestEmail')
        ->assertSet('testEmail', '');
});

test('SendTestEmailJob sends email successfully', function () {
    Mail::fake();

    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('email', [
        'sender_name'  => 'Test Store',
        'sender_email' => 'store@test.com',
    ]);

    $settingsService->saveGroup('general', [
        'store_name' => 'My Store',
    ]);

    $job = new SendTestEmailJob('recipient@test.com');
    $job->handle($settingsService);

    Mail::assertSent(TestEmail::class, function ($mail) {
        return $mail->hasTo('recipient@test.com');
    });
});

test('TestEmail mailable has correct subject', function () {
    $mailable = new TestEmail('Test Store', now());

    expect($mailable->envelope()->subject)
        ->toBe('Email de Teste - Test Store');
});

test('TestEmail mailable contains store information', function () {
    $mailable = new TestEmail('My Store', now());

    $mailable->assertSeeInHtml('My Store');
});

test('TestEmail mailable contains test date and time', function () {
    $testTime = now()->setTimezone('America/Sao_Paulo');
    $mailable = new TestEmail('Test Store', $testTime);

    $mailable->assertSeeInHtml($testTime->format('d/m/Y'));
    $mailable->assertSeeInHtml($testTime->format('H:i'));
});

test('SendTestEmailJob handles mail failure gracefully', function () {
    Mail::fake();
    Mail::shouldReceive('to')
        ->andThrow(new \Exception('SMTP connection failed'));

    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('email', [
        'sender_name'  => 'Test Store',
        'sender_email' => 'store@test.com',
    ]);

    $settingsService->saveGroup('general', [
        'store_name' => 'My Store',
    ]);

    $job = new SendTestEmailJob('recipient@test.com');

    expect(fn () => $job->handle($settingsService))
        ->toThrow(\Exception::class);
});

test('EmailSettings requires email provider to be configured before sending test', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', '')
        ->set('sender_email', '')
        ->set('testEmail', 'recipient@test.com')
        ->call('sendTestEmail')
        ->assertHasErrors(['sender_name', 'sender_email']);
});
