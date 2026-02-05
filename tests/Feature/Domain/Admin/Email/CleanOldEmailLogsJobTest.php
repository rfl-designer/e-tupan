<?php

declare(strict_types=1);

use App\Domain\Admin\Jobs\CleanOldEmailLogsJob;
use App\Domain\Admin\Models\EmailLog;
use Illuminate\Support\Facades\Log;

describe('Clean Old Email Logs Job', function () {
    it('deletes email logs older than 90 days', function () {
        // Create old logs (should be deleted)
        EmailLog::factory()->count(5)->old(91)->create();

        // Create recent logs (should be kept)
        EmailLog::factory()->count(3)->create();

        expect(EmailLog::count())->toBe(8);

        (new CleanOldEmailLogsJob)->handle();

        expect(EmailLog::count())->toBe(3);
    });

    it('keeps logs exactly 90 days old', function () {
        EmailLog::factory()->old(90)->create(['subject' => 'Exatamente 90 dias']);
        EmailLog::factory()->old(91)->create(['subject' => 'Mais de 90 dias']);

        (new CleanOldEmailLogsJob)->handle();

        expect(EmailLog::count())->toBe(1);
        expect(EmailLog::first()->subject)->toBe('Exatamente 90 dias');
    });

    it('uses configurable retention days', function () {
        EmailLog::factory()->old(31)->create();
        EmailLog::factory()->old(29)->create();

        (new CleanOldEmailLogsJob(retentionDays: 30))->handle();

        expect(EmailLog::count())->toBe(1);
    });

    it('logs the number of deleted entries', function () {
        Log::spy();

        EmailLog::factory()->count(5)->old(91)->create();

        (new CleanOldEmailLogsJob)->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, '5') && str_contains($message, 'email log'));
    });

    it('handles empty table gracefully', function () {
        Log::spy();

        (new CleanOldEmailLogsJob)->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, '0'));
    });

    it('implements ShouldQueue interface', function () {
        $job = new CleanOldEmailLogsJob;

        expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });
});
