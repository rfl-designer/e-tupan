<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Http\Controllers\Admin;

use App\Domain\Checkout\Models\PaymentLog;
use App\Domain\Checkout\Services\PaymentLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentLogController
{
    public function __construct(
        private readonly PaymentLogService $paymentLogService,
    ) {
    }

    /**
     * Display a listing of payment logs.
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'order_id',
            'payment_id',
            'gateway',
            'action',
            'status',
            'date_from',
            'date_to',
            'search',
        ]);

        $logs = $this->paymentLogService->getFiltered($filters, 25);

        $statistics = $this->paymentLogService->getStatistics(30);

        $gateways = PaymentLog::query()
            ->distinct()
            ->pluck('gateway')
            ->sort()
            ->values();

        $actions = PaymentLog::query()
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('admin.payments.logs', [
            'logs'       => $logs,
            'filters'    => $filters,
            'statistics' => $statistics,
            'gateways'   => $gateways,
            'actions'    => $actions,
        ]);
    }

    /**
     * Display a specific payment log.
     */
    public function show(PaymentLog $paymentLog): View
    {
        return view('admin.payments.log-details', [
            'log' => $paymentLog,
        ]);
    }
}
