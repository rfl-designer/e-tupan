<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Http\Controllers;

use App\Domain\Shipping\Http\Requests\SearchTrackingRequest;
use App\Domain\Shipping\Services\TrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TrackingController
{
    public function __construct(
        protected TrackingService $trackingService,
    ) {
    }

    /**
     * Show tracking search form.
     */
    public function index(): View
    {
        return view('storefront.tracking.index');
    }

    /**
     * Search for tracking by code.
     */
    public function search(SearchTrackingRequest $request): RedirectResponse
    {
        return redirect()->route('tracking.show', ['code' => $request->validated('code')]);
    }

    /**
     * Show tracking details.
     */
    public function show(string $code): View
    {
        $trackingInfo = $this->trackingService->getPublicTrackingInfo($code);

        return view('storefront.tracking.show', [
            'code'     => $code,
            'tracking' => $trackingInfo,
        ]);
    }
}
