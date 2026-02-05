<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Livewire;

use App\Domain\Cart\Events\ItemAddedToCart;
use App\Domain\Cart\Exceptions\{InsufficientStockException, ProductNotAvailableException};
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddToCart extends Component
{
    public int $productId;

    public ?int $variantId = null;

    public int $quantity = 1;

    public int $maxQuantity = 99;

    public bool $showModal = false;

    public ?string $addedItemName = null;

    public ?int $addedItemPrice = null;

    public bool $requiresVariant = false;

    public ?string $errorMessage = null;

    protected CartService $cartService;

    /**
     * @var array<int, array{id: int, name: string, price: int, stock: int}>
     */
    public array $variants = [];

    public function boot(CartService $cartService): void
    {
        $this->cartService = $cartService;
    }

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->loadProduct();
    }

    protected function loadProduct(): void
    {
        $product = Product::with('variants')->find($this->productId);

        if ($product === null) {
            return;
        }

        $this->requiresVariant = $product->isVariable();

        if ($this->requiresVariant) {
            $this->variants = $product->variants
                ->filter(fn (ProductVariant $v) => $v->is_active)
                ->map(fn (ProductVariant $v) => [
                    'id'    => $v->id,
                    'name'  => $v->getAttributeDescription() ?: $v->sku,
                    'price' => $v->getEffectivePrice(),
                    'stock' => $v->stock_quantity ?? 0,
                ])
                ->values()
                ->toArray();

            // Auto-select first variant if only one exists
            if (count($this->variants) === 1) {
                $this->variantId = $this->variants[0]['id'];
            }
        }

        $this->updateMaxQuantity();
    }

    public function updatedVariantId(): void
    {
        $this->updateMaxQuantity();
    }

    protected function updateMaxQuantity(): void
    {
        $product = Product::find($this->productId);

        if ($product === null) {
            return;
        }

        if ($this->variantId !== null) {
            $variant           = ProductVariant::find($this->variantId);
            $this->maxQuantity = $variant?->stock_quantity ?? 99;

            return;
        }

        if (!$product->manage_stock) {
            $this->maxQuantity = 99;

            return;
        }

        $this->maxQuantity = $product->stock_quantity ?? 0;
    }

    public function increment(): void
    {
        if ($this->quantity < $this->maxQuantity) {
            $this->quantity++;
        }
    }

    public function decrement(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function add(): void
    {
        $this->errorMessage = null;

        $product = Product::find($this->productId);

        if ($product === null) {
            $this->errorMessage = 'Produto nao encontrado.';

            return;
        }

        // Check if variant is required but not selected
        if ($this->requiresVariant && $this->variantId === null) {
            $this->errorMessage = 'Selecione uma variacao do produto.';

            return;
        }

        $variant = $this->variantId !== null ? ProductVariant::find($this->variantId) : null;

        try {
            $cart = $this->cartService->getOrCreate(
                userId: Auth::id(),
                sessionId: Auth::guest() ? session()->getId() : null,
            );

            $item = $this->cartService->addItem($cart, $product, $this->quantity, $variant);

            // Store info for modal
            $this->addedItemName  = $item->getDisplayName();
            $this->addedItemPrice = $item->getSubtotal();
            $this->showModal      = true;

            // Dispatch event for other components
            $this->dispatch('cart-updated', itemCount: $cart->itemCount());

            // Dispatch Laravel event
            ItemAddedToCart::dispatch($cart, $item);

            // Reset quantity
            $this->quantity = 1;

        } catch (InsufficientStockException $e) {
            $this->errorMessage = "Estoque insuficiente. Disponivel: {$e->availableQuantity} unidades.";
        } catch (ProductNotAvailableException) {
            $this->errorMessage = 'Este produto nao esta disponivel para compra.';
        }
    }

    public function closeModal(): void
    {
        $this->showModal      = false;
        $this->addedItemName  = null;
        $this->addedItemPrice = null;
    }

    public function goToCart(): void
    {
        $this->redirect(route('cart.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.cart.add-to-cart');
    }
}
