<?php

declare(strict_types = 1);

namespace App\Livewire\Storefront;

use App\Domain\Cart\Events\ItemAddedToCart;
use App\Domain\Cart\Exceptions\{InsufficientStockException, ProductNotAvailableException};
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Product, ProductImage, ProductVariant};
use App\Domain\Payment\Services\InstallmentCalculator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property-read string $metaTitle
 * @property-read string|null $metaDescription
 * @property-read array<int, array{name: string, url: string|null}> $breadcrumbs
 * @property-read array{max_interest_free: array{installments: int, value: int}|null, max_with_interest: array{installments: int, value: int}|null} $installments
 * @property-read Collection<int|string, array{attribute: \App\Domain\Catalog\Models\Attribute, values: Collection<int, array{value: \App\Domain\Catalog\Models\AttributeValue, in_stock: bool, variant_id: int|null}>}> $variantAttributes
 * @property-read ProductVariant|null $selectedVariant
 * @property-read int $currentPrice
 * @property-read bool $isCurrentlyInStock
 * @property-read bool $isCurrentlyLowStock
 * @property-read string $stockStatusLabel
 * @property-read EloquentCollection<int, ProductImage> $currentImages
 * @property-read bool $mustSelectVariant
 * @property-read bool $canAddToCart
 * @property-read EloquentCollection<int, Product> $relatedProducts
 */
#[Layout('components.storefront-layout')]
class ProductShow extends Component
{
    public Product $product;

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public int $maxQuantity = 99;

    public bool $showCartModal = false;

    public ?string $addedItemName = null;

    public ?int $addedItemPrice = null;

    public ?string $cartErrorMessage = null;

    protected CartService $cartService;

    public function boot(CartService $cartService): void
    {
        $this->cartService = $cartService;
    }

    public function mount(string $slug): void
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', ProductStatus::Active)
            ->with([
                'categories.parent',
                'images',
                'variants.attributeValues.attribute',
                'variants.images',
            ])
            ->first();

        if ($product === null) {
            throw new NotFoundHttpException('Produto não encontrado.');
        }

        $this->product = $product;
        $this->updateMaxQuantity();
    }

    /**
     * Select a variant by ID.
     */
    public function selectVariant(int $variantId): void
    {
        /** @var ProductVariant|null $variant */
        $variant = $this->product->variants->find($variantId);

        if ($variant !== null && $variant->is_active) {
            $this->selectedVariantId = $variantId;
            $this->updateMaxQuantity();

            // Reset quantity if it exceeds new max
            if ($this->quantity > $this->maxQuantity) {
                $this->quantity = max(1, $this->maxQuantity);
            }
        }
    }

    /**
     * Update the maximum quantity based on stock.
     */
    protected function updateMaxQuantity(): void
    {
        if ($this->selectedVariantId !== null) {
            /** @var ProductVariant|null $variant */
            $variant = $this->product->variants->find($this->selectedVariantId);

            if ($variant !== null) {
                $this->maxQuantity = $this->product->manage_stock
                    ? max(0, $variant->stock_quantity ?? 0)
                    : 99;

                return;
            }
        }

        if (!$this->product->manage_stock) {
            $this->maxQuantity = 99;

            return;
        }

        $this->maxQuantity = max(0, $this->product->stock_quantity ?? 0);
    }

    /**
     * Increment the quantity.
     */
    public function incrementQuantity(): void
    {
        if ($this->quantity < $this->maxQuantity) {
            $this->quantity++;
        }
    }

    /**
     * Decrement the quantity.
     */
    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    /**
     * Add the product to the cart.
     */
    public function addToCart(): void
    {
        $this->cartErrorMessage = null;

        // Check if variant is required but not selected
        if ($this->mustSelectVariant) {
            $this->cartErrorMessage = 'Selecione uma variacao do produto.';

            return;
        }

        $variant = $this->selectedVariant;

        try {
            $cart = $this->cartService->getOrCreate(
                userId: Auth::id(),
                sessionId: Auth::guest() ? session()->getId() : null,
            );

            $item = $this->cartService->addItem($cart, $this->product, $this->quantity, $variant);

            // Store info for modal
            $this->addedItemName  = $item->getDisplayName();
            $this->addedItemPrice = $item->getSubtotal();
            $this->showCartModal  = true;

            // Dispatch event for other components (like MiniCart)
            $this->dispatch('cart-updated', itemCount: $cart->itemCount());

            // Dispatch Laravel event
            ItemAddedToCart::dispatch($cart, $item);

            // Reset quantity
            $this->quantity = 1;

        } catch (InsufficientStockException $e) {
            $this->cartErrorMessage = "Estoque insuficiente. Disponivel: {$e->availableQuantity} unidades.";
        } catch (ProductNotAvailableException) {
            $this->cartErrorMessage = 'Este produto nao esta disponivel para compra.';
        }
    }

    /**
     * Close the cart confirmation modal.
     */
    public function closeCartModal(): void
    {
        $this->showCartModal  = false;
        $this->addedItemName  = null;
        $this->addedItemPrice = null;
    }

    /**
     * Navigate to the cart page.
     */
    public function goToCart(): void
    {
        $this->redirect(route('cart.index'), navigate: true);
    }

    /**
     * Check if the product can be added to cart.
     */
    public function getCanAddToCartProperty(): bool
    {
        if (!$this->isCurrentlyInStock) {
            return false;
        }

        if ($this->mustSelectVariant) {
            return false;
        }

        return true;
    }

    /**
     * Get related products from the same categories.
     *
     * @return EloquentCollection<int, Product>
     */
    public function getRelatedProductsProperty(): EloquentCollection
    {
        $categoryIds = $this->product->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return new EloquentCollection();
        }

        return Product::query()
            ->where('id', '!=', $this->product->id)
            ->where('status', ProductStatus::Active)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->with(['images' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->limit(4)
            ->get();
    }

    /**
     * Get the meta title for SEO.
     */
    public function getMetaTitleProperty(): string
    {
        return $this->product->meta_title ?? $this->product->name;
    }

    /**
     * Get the meta description for SEO.
     */
    public function getMetaDescriptionProperty(): ?string
    {
        return $this->product->meta_description ?? $this->product->short_description;
    }

    /**
     * Get the breadcrumb items for navigation.
     *
     * @return array<int, array{name: string, url: string|null}>
     */
    public function getBreadcrumbsProperty(): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Produtos', 'url' => route('products.index')],
        ];

        /** @var \App\Domain\Catalog\Models\Category|null $category */
        $category = $this->product->categories->first();

        if ($category !== null) {
            // Add parent category if exists
            /** @var \App\Domain\Catalog\Models\Category|null $parentCategory */
            $parentCategory = $category->parent;

            if ($parentCategory !== null) {
                $breadcrumbs[] = [
                    'name' => $parentCategory->name,
                    'url'  => route('products.index', ['categoria' => $parentCategory->slug]),
                ];
            }

            $breadcrumbs[] = [
                'name' => $category->name,
                'url'  => route('products.index', ['categoria' => $category->slug]),
            ];
        }

        // Current product (no link)
        $breadcrumbs[] = [
            'name' => $this->product->name,
            'url'  => null,
        ];

        return $breadcrumbs;
    }

    /**
     * Get the installment options for the product.
     *
     * @return array{max_interest_free: array{installments: int, value: int}|null, max_with_interest: array{installments: int, value: int}|null}
     */
    public function getInstallmentsProperty(): array
    {
        $calculator = new InstallmentCalculator();

        return $calculator->getDisplaySummary($this->currentPrice);
    }

    /**
     * Get variant attributes with their available values.
     *
     * @return Collection<int|string, array{attribute: \App\Domain\Catalog\Models\Attribute, values: Collection<int, array{value: \App\Domain\Catalog\Models\AttributeValue, in_stock: bool, variant_id: int|null}>}>
     */
    public function getVariantAttributesProperty(): Collection
    {
        if (!$this->product->isVariable() || $this->product->variants->isEmpty()) {
            return collect();
        }

        /** @var array<int, array{attribute: \App\Domain\Catalog\Models\Attribute, values: array<int, array{value: \App\Domain\Catalog\Models\AttributeValue, in_stock: bool, variant_id: int}>}> $attributes */
        $attributes = [];

        /** @var ProductVariant $variant */
        foreach ($this->product->variants as $variant) {
            if (!$variant->is_active) {
                continue;
            }

            /** @var \App\Domain\Catalog\Models\AttributeValue $attributeValue */
            foreach ($variant->attributeValues as $attributeValue) {
                /** @var \App\Domain\Catalog\Models\Attribute $attribute */
                $attribute = $attributeValue->attribute;
                $attrKey   = $attribute->id;

                if (!isset($attributes[$attrKey])) {
                    $attributes[$attrKey] = [
                        'attribute' => $attribute,
                        'values'    => [],
                    ];
                }

                $valueKey = $attributeValue->id;

                if (!isset($attributes[$attrKey]['values'][$valueKey])) {
                    $attributes[$attrKey]['values'][$valueKey] = [
                        'value'      => $attributeValue,
                        'in_stock'   => $variant->isInStock(),
                        'variant_id' => $variant->id,
                    ];
                }
            }
        }

        // Convert to collection and sort by attribute position
        $result = collect($attributes)
            ->map(fn (array $item) => [
                'attribute' => $item['attribute'],
                'values'    => collect($item['values']),
            ])
            ->sortBy(fn (array $item) => $item['attribute']->position);

        /** @phpstan-ignore return.type (PHPStan não consegue inferir corretamente o tipo após sortBy) */
        return $result;
    }

    /**
     * Get the currently selected variant.
     */
    public function getSelectedVariantProperty(): ?ProductVariant
    {
        if ($this->selectedVariantId === null) {
            return null;
        }

        /** @var ProductVariant|null */
        return $this->product->variants->find($this->selectedVariantId);
    }

    /**
     * Get the current effective price (variant or product price).
     */
    public function getCurrentPriceProperty(): int
    {
        $variant = $this->selectedVariant;

        if ($variant !== null) {
            return $variant->getEffectivePrice();
        }

        return $this->product->getCurrentPrice();
    }

    /**
     * Get the current stock status.
     */
    public function getIsCurrentlyInStockProperty(): bool
    {
        $variant = $this->selectedVariant;

        if ($variant !== null) {
            return $variant->isInStock();
        }

        return $this->product->isInStock();
    }

    /**
     * Check if the current stock level is low.
     */
    public function getIsCurrentlyLowStockProperty(): bool
    {
        $variant = $this->selectedVariant;

        if ($variant !== null) {
            return $variant->isLowStock();
        }

        return $this->product->isLowStock();
    }

    /**
     * Get the stock status label for display.
     */
    public function getStockStatusLabelProperty(): string
    {
        if (!$this->isCurrentlyInStock) {
            return 'Esgotado';
        }

        if ($this->isCurrentlyLowStock) {
            return 'Ultimas unidades';
        }

        return 'Em estoque';
    }

    /**
     * Get the current images to display.
     *
     * @return EloquentCollection<int, ProductImage>
     */
    public function getCurrentImagesProperty(): EloquentCollection
    {
        $variant = $this->selectedVariant;

        if ($variant !== null && $variant->images->isNotEmpty()) {
            /** @var EloquentCollection<int, ProductImage> */
            return $variant->images;
        }

        /** @var EloquentCollection<int, ProductImage> */
        return $this->product->images;
    }

    /**
     * Check if a variant must be selected before adding to cart.
     */
    public function getMustSelectVariantProperty(): bool
    {
        return $this->product->isVariable()
            && $this->product->variants->isNotEmpty()
            && $this->selectedVariantId === null;
    }

    public function render(): View
    {
        return view('livewire.storefront.product-show')
            ->title($this->metaTitle)
            ->layoutData([
                'metaTitle'       => $this->metaTitle,
                'metaDescription' => $this->metaDescription,
            ]);
    }
}
