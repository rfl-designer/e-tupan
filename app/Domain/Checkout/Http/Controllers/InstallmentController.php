<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Http\Controllers;

use App\Domain\Checkout\Http\Requests\GetInstallmentsRequest;
use App\Domain\Checkout\Services\InstallmentService;
use Illuminate\Http\JsonResponse;

class InstallmentController
{
    public function __construct(
        private readonly InstallmentService $installmentService,
    ) {
    }

    /**
     * Get installment options for a given amount.
     */
    public function __invoke(GetInstallmentsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $amount    = (int) $validated['amount'];
        $cardBrand = $validated['card_brand'] ?? null;

        $installments = $this->installmentService->getInstallments($amount, $cardBrand);

        return response()->json([
            'installments' => $installments->map(fn ($option) => $option->toArray())->values(),
        ]);
    }
}
