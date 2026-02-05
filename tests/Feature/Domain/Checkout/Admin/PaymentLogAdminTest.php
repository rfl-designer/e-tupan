<?php

declare(strict_types = 1);

use App\Domain\Checkout\Http\Controllers\Admin\PaymentLogController;
use App\Domain\Checkout\Models\PaymentLog;
use App\Domain\Checkout\Services\PaymentLogService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Payment Logs Admin Controller', function () {
    it('requires authentication to access logs', function () {
        $response = $this->get(route('admin.payments.logs'));

        $response->assertRedirect(route('admin.login'));
    });

    it('controller returns correct view with logs', function () {
        PaymentLog::factory()->count(5)->create();

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request();

        $view = $controller->index($request);

        expect($view->name())->toBe('admin.payments.logs');
        expect($view->getData())->toHaveKeys(['logs', 'filters', 'statistics', 'gateways', 'actions']);
    });

    it('controller filters logs by gateway', function () {
        PaymentLog::factory()->count(3)->create(['gateway' => 'mercadopago']);
        PaymentLog::factory()->count(2)->create(['gateway' => 'mock']);

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request(['gateway' => 'mercadopago']);

        $view = $controller->index($request);

        expect($view->getData()['logs']->total())->toBe(3);
    });

    it('controller filters logs by status', function () {
        PaymentLog::factory()->count(3)->success()->create();
        PaymentLog::factory()->count(2)->failed()->create();

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request(['status' => 'success']);

        $view = $controller->index($request);

        expect($view->getData()['logs']->total())->toBe(3);
    });

    it('controller filters logs by action', function () {
        PaymentLog::factory()->count(3)->processCard()->create();
        PaymentLog::factory()->count(2)->generatePix()->create();

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request(['action' => 'process_card']);

        $view = $controller->index($request);

        expect($view->getData()['logs']->total())->toBe(3);
    });

    it('controller searches logs by transaction id', function () {
        PaymentLog::factory()->create(['transaction_id' => 'TXN-ABC123']);
        PaymentLog::factory()->create(['transaction_id' => 'TXN-XYZ789']);

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request(['search' => 'ABC123']);

        $view = $controller->index($request);

        expect($view->getData()['logs']->total())->toBe(1);
    });

    it('controller paginates logs', function () {
        PaymentLog::factory()->count(50)->create();

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request();

        $view = $controller->index($request);

        expect($view->getData()['logs']->perPage())->toBe(25);
    });

    it('controller provides statistics', function () {
        PaymentLog::factory()->count(10)->success()->create();
        PaymentLog::factory()->count(5)->failed()->create();

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request();

        $view = $controller->index($request);

        $statistics = $view->getData()['statistics'];

        expect($statistics['total'])->toBe(15);
        expect($statistics['successful'])->toBe(10);
        expect($statistics['failed'])->toBe(5);
    });

    it('controller provides available gateways for filter', function () {
        PaymentLog::factory()->create(['gateway' => 'mercadopago']);
        PaymentLog::factory()->create(['gateway' => 'mock']);

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request();

        $view     = $controller->index($request);
        $gateways = $view->getData()['gateways'];

        expect($gateways->toArray())->toContain('mercadopago');
        expect($gateways->toArray())->toContain('mock');
    });

    it('controller provides available actions for filter', function () {
        PaymentLog::factory()->processCard()->create();
        PaymentLog::factory()->generatePix()->create();

        $controller = new PaymentLogController(new PaymentLogService());
        $request    = new \Illuminate\Http\Request();

        $view    = $controller->index($request);
        $actions = $view->getData()['actions'];

        expect($actions->toArray())->toContain('process_card');
        expect($actions->toArray())->toContain('generate_pix');
    });
});
