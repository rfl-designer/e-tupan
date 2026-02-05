<?php

declare(strict_types=1);

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Jobs\ResendEmailJob;
use App\Domain\Admin\Livewire\Settings\EmailLogList;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\Fakes\FakeResendableMailable;

beforeEach(function () {
    $this->admin = Admin::factory()->withTwoFactor()->create();
    Queue::fake();
});

describe('Resend Button Visibility', function () {
    it('shows resend button for failed emails within 7 days', function () {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(3),
        ]);

        Livewire::test(EmailLogList::class)
            ->assertSeeHtml('wire:click="confirmResend('.$log->id.')"');
    });

    it('does not show resend button for non-resendable emails', function (string $state, ?int $daysAgo) {
        $log = EmailLog::factory()->{$state}()->create([
            'created_at' => $daysAgo ? now()->subDays($daysAgo) : now(),
        ]);

        Livewire::test(EmailLogList::class)
            ->assertDontSeeHtml('wire:click="confirmResend('.$log->id.')"');
    })->with([
        'sent emails' => ['sent', null],
        'pending emails' => ['pending', null],
        'failed emails older than 7 days' => ['failed', 8],
    ]);
});

describe('Resend Confirmation Modal', function () {
    it('opens confirmation modal when confirmResend is called', function () {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(3),
        ]);

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->assertSet('showResendModal', true)
            ->assertSet('logToResend.id', $log->id);
    });

    it('closes confirmation modal when cancelResend is called', function () {
        $log = EmailLog::factory()->failed()->create();

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->call('cancelResend')
            ->assertSet('showResendModal', false)
            ->assertSet('logToResend', null);
    });

    it('shows email details in confirmation modal', function () {
        $log = EmailLog::factory()->failed()->create([
            'recipient' => 'cliente@teste.com',
            'subject' => 'Confirmacao do Pedido',
        ]);

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->assertSee('cliente@teste.com')
            ->assertSee('Confirmacao do Pedido');
    });
});

describe('Resend Email Action', function () {
    it('dispatches ResendEmailJob when resend is confirmed', function () {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(3),
        ]);

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->call('resend');

        Queue::assertPushed(ResendEmailJob::class, fn ($job) => $job->emailLog->id === $log->id);
    });

    it('closes modal after resend is dispatched', function () {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(3),
        ]);

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->call('resend')
            ->assertSet('showResendModal', false)
            ->assertSet('logToResend', null);
    });

    it('dispatches resend-queued event after resend', function () {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(3),
        ]);

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->call('resend')
            ->assertDispatched('resend-queued');
    });

    it('prevents resend if email is older than 7 days', function () {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(8),
        ]);

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->call('resend');

        Queue::assertNotPushed(ResendEmailJob::class);
    });

    it('prevents resend if email is not failed', function () {
        $log = EmailLog::factory()->sent()->create();

        Livewire::test(EmailLogList::class)
            ->call('confirmResend', $log->id)
            ->call('resend');

        Queue::assertNotPushed(ResendEmailJob::class);
    });
});

describe('EmailLog canBeResent Method', function () {
    it('returns true for resendable emails', function (int $daysAgo) {
        $log = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays($daysAgo),
        ]);

        expect($log->canBeResent())->toBeTrue();
    })->with([
        'within 7 days' => [6],
        'exactly 7 days old' => [7],
    ]);

    it('returns false for non-resendable emails', function (string $state, ?int $daysAgo) {
        $log = EmailLog::factory()->{$state}()->create([
            'created_at' => $daysAgo ? now()->subDays($daysAgo) : now(),
        ]);

        expect($log->canBeResent())->toBeFalse();
    })->with([
        'sent emails' => ['sent', null],
        'pending emails' => ['pending', null],
        'failed emails older than 7 days' => ['failed', 8],
    ]);
});

describe('ResendEmailJob', function () {
    it('is queued on the emails queue', function () {
        $log = EmailLog::factory()->failed()->create();

        ResendEmailJob::dispatch($log);

        Queue::assertPushed(ResendEmailJob::class, fn ($job) => $job->queue === 'emails');
    });

    it('has correct job configuration', function () {
        $log = EmailLog::factory()->failed()->create();
        $job = new ResendEmailJob($log);

        expect($job)
            ->tries->toBe(3)
            ->timeout->toBe(30)
            ->backoff()->toBe([10, 60, 300]);
    });
});

describe('ResendEmailJob Execution', function () {
    it('creates a new email log entry with reference to original', function () {
        Queue::fake()->except([ResendEmailJob::class]);
        Mail::fake();

        $original = EmailLog::factory()->failed()->create([
            'recipient' => 'teste@example.com',
            'subject' => 'Assunto Original',
            'mailable_class' => FakeResendableMailable::class,
            'driver' => 'smtp',
        ]);

        ResendEmailJob::dispatchSync($original);

        expect(EmailLog::count())->toBe(2);

        $newLog = EmailLog::where('resent_from_id', $original->id)->first();

        expect($newLog)
            ->recipient->toBe('teste@example.com')
            ->subject->toBe('Assunto Original')
            ->resent_from_id->toBe($original->id);
    });

    it('marks new log as sent on success', function () {
        Queue::fake()->except([ResendEmailJob::class]);
        Mail::fake();

        $original = EmailLog::factory()->failed()->create([
            'mailable_class' => FakeResendableMailable::class,
        ]);

        ResendEmailJob::dispatchSync($original);

        $newLog = EmailLog::where('resent_from_id', $original->id)->first();

        expect($newLog->status)->toBe(EmailLogStatus::Sent);
    });

    it('sends email to the original recipient', function () {
        Queue::fake()->except([ResendEmailJob::class]);
        Mail::fake();

        $original = EmailLog::factory()->failed()->create([
            'recipient' => 'destinatario@example.com',
            'mailable_class' => FakeResendableMailable::class,
        ]);

        ResendEmailJob::dispatchSync($original);

        Mail::assertQueued(FakeResendableMailable::class, fn ($mail) => $mail->hasTo('destinatario@example.com'));
    });

    it('does not resend if email cannot be resent', function () {
        Queue::fake()->except([ResendEmailJob::class]);
        Mail::fake();

        $original = EmailLog::factory()->failed()->create([
            'created_at' => now()->subDays(10),
        ]);

        ResendEmailJob::dispatchSync($original);

        expect(EmailLog::count())->toBe(1);
        Mail::assertNothingSent();
    });

    it('marks new log as failed when mailable class does not exist', function () {
        Queue::fake()->except([ResendEmailJob::class]);
        Mail::fake();

        $original = EmailLog::factory()->failed()->create([
            'mailable_class' => 'NonExistent\\Mailable\\Class',
        ]);

        expect(fn () => ResendEmailJob::dispatchSync($original))->toThrow(\RuntimeException::class);

        $newLog = EmailLog::where('resent_from_id', $original->id)->first();
        expect($newLog->status)->toBe(EmailLogStatus::Failed)
            ->and($newLog->error_message)->toContain('does not exist');
    });
});

describe('Email Log Relationships', function () {
    it('tracks resent emails with resent_from_id', function () {
        $original = EmailLog::factory()->failed()->create();
        $resent = EmailLog::factory()->resentFrom($original)->create();

        expect($resent->resent_from_id)->toBe($original->id)
            ->and($resent->resentFrom->id)->toBe($original->id);
    });

    it('can get resent copies from original log', function () {
        $original = EmailLog::factory()->failed()->create();
        EmailLog::factory()->resentFrom($original)->create();
        EmailLog::factory()->resentFrom($original)->create();

        expect($original->resentCopies)->toHaveCount(2);
    });
});
