<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Http\Controllers;

use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Http\Requests\AdjustStockRequest;
use App\Domain\Inventory\Services\StockService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
    ) {
    }

    /**
     * Display the stock dashboard page.
     */
    public function dashboard(): View
    {
        return view('admin.inventory.dashboard');
    }

    /**
     * Display the stock listing page.
     */
    public function index(): View
    {
        return view('admin.inventory.index');
    }

    /**
     * Display the stock movements page.
     */
    public function movements(): View
    {
        return view('admin.inventory.movements');
    }

    /**
     * Adjust stock for a product or variant.
     */
    public function adjust(AdjustStockRequest $request): RedirectResponse
    {
        $stockable = $this->stockService->resolveStockable(
            type: $request->validated('stockable_type'),
            id: (int) $request->validated('stockable_id'),
        );

        if ($stockable === null) {
            return back()->with('error', 'Item nao encontrado.');
        }

        try {
            $movement = $this->stockService->adjust(
                stockable: $stockable,
                quantity: $request->getAdjustedQuantity(),
                type: $request->getMovementType(),
                notes: $request->validated('notes'),
            );

            return back()->with('success', sprintf(
                'Estoque ajustado com sucesso. Novo saldo: %d unidades.',
                $movement->quantity_after,
            ));
        } catch (InsufficientStockException $e) {
            return back()->with('error', sprintf(
                'Estoque insuficiente. Disponivel: %d unidades.',
                $e->getAvailableQuantity(),
            ));
        }
    }
}
