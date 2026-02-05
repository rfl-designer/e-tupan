<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\Settings\EmailLogList;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\EmailLog;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->withTwoFactor()->create();
});

describe('Email Log List Page', function () {
    it('renders email log list page', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.email-logs.index'))
            ->assertSuccessful()
            ->assertSeeLivewire(EmailLogList::class);
    });

    it('displays the page title', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.email-logs.index'))
            ->assertSee('Logs de Email');
    });
});

describe('Email Log Listing', function () {
    it('displays email logs in the list', function () {
        $log = EmailLog::factory()->sent()->create([
            'recipient' => 'cliente@example.com',
            'subject' => 'Confirmacao do Pedido #12345',
        ]);

        Livewire::test(EmailLogList::class)
            ->assertSee('cliente@example.com')
            ->assertSee('Confirmacao do Pedido #12345');
    });

    it('displays email log status', function () {
        EmailLog::factory()->sent()->create();
        EmailLog::factory()->failed()->create();

        Livewire::test(EmailLogList::class)
            ->assertSee('Enviado')
            ->assertSee('Falhou');
    });

    it('displays mailable class as readable name', function () {
        EmailLog::factory()->create([
            'mailable_class' => 'App\\Domain\\Admin\\Mail\\TestEmailConfiguration',
        ]);

        Livewire::test(EmailLogList::class)
            ->assertSee('TestEmailConfiguration');
    });

    it('displays driver used for sending', function () {
        EmailLog::factory()->create(['driver' => 'mailgun']);

        Livewire::test(EmailLogList::class)
            ->assertSee('mailgun');
    });

    it('shows empty state when no logs exist', function () {
        Livewire::test(EmailLogList::class)
            ->assertSee('Nenhum log de email encontrado');
    });
});

describe('Email Log Pagination', function () {
    it('paginates logs with 20 per page', function () {
        EmailLog::factory()->count(25)->sent()->create();

        Livewire::test(EmailLogList::class)
            ->assertSee('1')
            ->assertSee('2')
            ->assertViewHas('logs', fn ($logs) => $logs->count() === 20);
    });

    it('orders logs by created_at descending', function () {
        $oldLog = EmailLog::factory()->create([
            'subject' => 'Email Antigo',
            'created_at' => now()->subDays(5),
        ]);
        $newLog = EmailLog::factory()->create([
            'subject' => 'Email Recente',
            'created_at' => now(),
        ]);

        $component = Livewire::test(EmailLogList::class);

        $logs = $component->viewData('logs');
        expect($logs->first()->id)->toBe($newLog->id);
    });
});

describe('Email Log Search', function () {
    it('searches logs by recipient email', function () {
        EmailLog::factory()->create(['recipient' => 'joao@example.com']);
        EmailLog::factory()->create(['recipient' => 'maria@example.com']);

        Livewire::test(EmailLogList::class)
            ->set('search', 'joao')
            ->assertSee('joao@example.com')
            ->assertDontSee('maria@example.com');
    });

    it('searches logs by subject', function () {
        EmailLog::factory()->create(['subject' => 'Pedido Confirmado']);
        EmailLog::factory()->create(['subject' => 'Senha Resetada']);

        Livewire::test(EmailLogList::class)
            ->set('search', 'Pedido')
            ->assertSee('Pedido Confirmado')
            ->assertDontSee('Senha Resetada');
    });

    it('resets page when searching', function () {
        EmailLog::factory()->count(25)->create();

        Livewire::test(EmailLogList::class)
            ->call('gotoPage', 2)
            ->set('search', 'test')
            ->assertNotSet('paginators.page', 2);
    });
});

describe('Email Log Filters', function () {
    it('filters by sent status', function () {
        EmailLog::factory()->sent()->create(['subject' => 'Enviado com sucesso']);
        EmailLog::factory()->failed()->create(['subject' => 'Falhou no envio']);

        Livewire::test(EmailLogList::class)
            ->set('filterStatus', 'sent')
            ->assertSee('Enviado com sucesso')
            ->assertDontSee('Falhou no envio');
    });

    it('filters by failed status', function () {
        EmailLog::factory()->sent()->create(['subject' => 'Enviado com sucesso']);
        EmailLog::factory()->failed()->create(['subject' => 'Falhou no envio']);

        Livewire::test(EmailLogList::class)
            ->set('filterStatus', 'failed')
            ->assertSee('Falhou no envio')
            ->assertDontSee('Enviado com sucesso');
    });

    it('filters by mailable class', function () {
        EmailLog::factory()->create([
            'subject' => 'Teste Config',
            'mailable_class' => 'App\\Domain\\Admin\\Mail\\TestEmailConfiguration',
        ]);
        EmailLog::factory()->create([
            'subject' => 'Confirmacao Pedido',
            'mailable_class' => 'App\\Domain\\Checkout\\Mail\\OrderConfirmation',
        ]);

        Livewire::test(EmailLogList::class)
            ->set('filterMailableClass', 'App\\Domain\\Admin\\Mail\\TestEmailConfiguration')
            ->assertSee('Teste Config')
            ->assertDontSee('Confirmacao Pedido');
    });

    it('filters by date range - from date', function () {
        EmailLog::factory()->create([
            'subject' => 'Email Antigo',
            'created_at' => now()->subDays(10),
        ]);
        EmailLog::factory()->create([
            'subject' => 'Email Recente',
            'created_at' => now()->subDays(2),
        ]);

        Livewire::test(EmailLogList::class)
            ->set('filterDateFrom', now()->subDays(5)->format('Y-m-d'))
            ->assertSee('Email Recente')
            ->assertDontSee('Email Antigo');
    });

    it('filters by date range - to date', function () {
        EmailLog::factory()->create([
            'subject' => 'Email Antigo',
            'created_at' => now()->subDays(10),
        ]);
        EmailLog::factory()->create([
            'subject' => 'Email Recente',
            'created_at' => now()->subDays(2),
        ]);

        Livewire::test(EmailLogList::class)
            ->set('filterDateTo', now()->subDays(5)->format('Y-m-d'))
            ->assertSee('Email Antigo')
            ->assertDontSee('Email Recente');
    });

    it('combines multiple filters', function () {
        EmailLog::factory()->sent()->create([
            'recipient' => 'joao@example.com',
            'subject' => 'Email Joao Enviado',
        ]);
        EmailLog::factory()->failed()->create([
            'recipient' => 'joao@example.com',
            'subject' => 'Email Joao Falhou',
        ]);
        EmailLog::factory()->sent()->create([
            'recipient' => 'maria@example.com',
            'subject' => 'Email Maria Enviado',
        ]);

        Livewire::test(EmailLogList::class)
            ->set('search', 'joao')
            ->set('filterStatus', 'sent')
            ->assertSee('Email Joao Enviado')
            ->assertDontSee('Email Joao Falhou')
            ->assertDontSee('Email Maria Enviado');
    });

    it('clears all filters', function () {
        Livewire::test(EmailLogList::class)
            ->set('search', 'test')
            ->set('filterStatus', 'sent')
            ->set('filterMailableClass', 'App\\Test')
            ->set('filterDateFrom', '2024-01-01')
            ->set('filterDateTo', '2024-12-31')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('filterStatus', '')
            ->assertSet('filterMailableClass', '')
            ->assertSet('filterDateFrom', '')
            ->assertSet('filterDateTo', '');
    });
});

describe('Email Log Details Modal', function () {
    it('shows log details in modal', function () {
        $log = EmailLog::factory()->failed()->create([
            'recipient' => 'teste@example.com',
            'subject' => 'Assunto do Email',
            'mailable_class' => 'App\\Mail\\TestMail',
            'driver' => 'smtp',
            'error_message' => 'Connection timeout',
        ]);

        Livewire::test(EmailLogList::class)
            ->call('showDetails', $log->id)
            ->assertSet('selectedLog.id', $log->id)
            ->assertSet('showDetailsModal', true);
    });

    it('closes details modal', function () {
        $log = EmailLog::factory()->create();

        Livewire::test(EmailLogList::class)
            ->call('showDetails', $log->id)
            ->assertSet('showDetailsModal', true)
            ->call('closeDetails')
            ->assertSet('showDetailsModal', false)
            ->assertSet('selectedLog', null);
    });
});

describe('Email Log Mailable Classes Dropdown', function () {
    it('provides unique mailable classes for filter dropdown', function () {
        EmailLog::factory()->create(['mailable_class' => 'App\\Mail\\ClassA']);
        EmailLog::factory()->create(['mailable_class' => 'App\\Mail\\ClassB']);
        EmailLog::factory()->create(['mailable_class' => 'App\\Mail\\ClassA']); // duplicate

        $component = Livewire::test(EmailLogList::class);
        $mailableClasses = $component->viewData('mailableClasses');

        expect($mailableClasses)->toHaveCount(2)
            ->and($mailableClasses)->toContain('App\\Mail\\ClassA')
            ->and($mailableClasses)->toContain('App\\Mail\\ClassB');
    });
});

describe('Email Log Status Options', function () {
    it('provides status options for filter dropdown', function () {
        $component = Livewire::test(EmailLogList::class);
        $statuses = $component->viewData('statuses');

        expect($statuses)->toHaveCount(3)
            ->and($statuses)->toHaveKey('sent')
            ->and($statuses)->toHaveKey('failed')
            ->and($statuses)->toHaveKey('pending');
    });
});
