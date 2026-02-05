<?php

declare(strict_types = 1);

namespace App\Livewire\Storefront;

use App\Domain\Admin\Services\SettingsService;
use App\Domain\Catalog\Models\{Attribute, Category, Product};
use App\Domain\Catalog\Services\ProductSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\{Builder, Collection};
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\{Computed, Url};
use Livewire\{Component, WithPagination};

class ProductList extends Component
{
    use WithPagination;

    #[Url(as: 'categoria')]
    public string $categoria = '';

    #[Url(as: 'preco_min')]
    public ?int $precoMin = null;

    #[Url(as: 'preco_max')]
    public ?int $precoMax = null;

    /** @var array<string, array<int>> */
    #[Url(as: 'atributos')]
    public array $atributos = [];

    #[Url(as: 'ordenar')]
    public string $ordenar = 'recentes';

    #[Url(as: 'promocao')]
    public bool $promocao = false;

    #[Url(as: 'q')]
    public string $q = '';

    /** @var array{0: int, 1: int} */
    public array $precoRange = [0, 1000];

    protected int $perPage = 12;

    protected int $maxPriceDefault = 1000;

    public function mount(): void
    {
        $this->initializePriceRange();
    }

    public function updatedCategoria(): void
    {
        $this->resetPage();
    }

    public function updatedPrecoMin(): void
    {
        $this->syncRangeFromMinMax();
        $this->resetPage();
    }

    public function updatedPrecoMax(): void
    {
        $this->syncRangeFromMinMax();
        $this->resetPage();
    }

    public function updatedPrecoRange(): void
    {
        $this->syncMinMaxFromRange();
        $this->resetPage();
    }

    public function updatedAtributos(): void
    {
        $this->resetPage();
    }

    public function updatedOrdenar(): void
    {
        $this->resetPage();
    }

    public function updatedPromocao(): void
    {
        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->q = '';
        $this->resetPage();
    }

    public function clearCategory(): void
    {
        $this->categoria = '';
        $this->resetPage();
    }

    public function clearPriceFilter(): void
    {
        $this->precoMin   = null;
        $this->precoMax   = null;
        $this->precoRange = [0, $this->maxPriceDefault];
        $this->resetPage();
    }

    public function clearAttributeFilter(string $attributeSlug): void
    {
        unset($this->atributos[$attributeSlug]);
        $this->resetPage();
    }

    public function clearAllAttributeFilters(): void
    {
        $this->atributos = [];
        $this->resetPage();
    }

    public function hasAttributeFilter(): bool
    {
        foreach ($this->atributos as $valueIds) {
            if (is_array($valueIds) && count($valueIds) > 0) {
                return true;
            }
        }

        return false;
    }

    public function clearPromoFilter(): void
    {
        $this->promocao = false;
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->categoria  = '';
        $this->precoMin   = null;
        $this->precoMax   = null;
        $this->precoRange = [0, $this->maxPriceDefault];
        $this->atributos  = [];
        $this->promocao   = false;
        $this->resetPage();
    }

    private function initializePriceRange(): void
    {
        $min              = $this->precoMin ?? 0;
        $max              = $this->precoMax ?? $this->maxPriceDefault;
        $this->precoRange = [$min, $max];
    }

    private function syncRangeFromMinMax(): void
    {
        $this->precoRange = [
            $this->precoMin ?? 0,
            $this->precoMax ?? $this->maxPriceDefault,
        ];
    }

    private function syncMinMaxFromRange(): void
    {
        $min = $this->precoRange[0] ?? 0;
        $max = $this->precoRange[1] ?? $this->maxPriceDefault;

        // Only set if different from defaults (to keep URL clean)
        $this->precoMin = $min > 0 ? $min : null;
        $this->precoMax = $max < $this->maxPriceDefault ? $max : null;
    }

    public function render(): View
    {
        $products = $this->products();

        $layoutData = [
            'title'           => $this->getPageTitle(),
            'metaDescription' => $this->getMetaDescription($products->total()),
            'canonicalUrl'    => $this->getCanonicalUrl(),
        ];

        // Add noindex for pages that shouldn't be indexed
        $metaRobots = $this->getMetaRobots($products->total());

        if ($metaRobots !== null) {
            $layoutData['metaRobots'] = $metaRobots;
        }

        return view('livewire.storefront.product-list', [
            'products' => $products,
        ])->layout('components.storefront-layout', $layoutData);
    }

    /**
     * @return LengthAwarePaginator<Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->active()
            ->with(['categories', 'images']);

        $this->applySearchFilter($query);

        if ($this->categoria !== '') {
            $categoryIds = $this->getCategoryIdsWithChildren($this->categoria);

            if ($categoryIds->isNotEmpty()) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        $this->applyPriceFilter($query);
        $this->applyAttributeFilter($query);
        $this->applyPromoFilter($query);
        $this->applyOrdering($query);

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function totalProducts(): int
    {
        $query = Product::query()->active();

        $this->applySearchFilter($query);

        if ($this->categoria !== '') {
            $categoryIds = $this->getCategoryIdsWithChildren($this->categoria);

            if ($categoryIds->isNotEmpty()) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        $this->applyPriceFilter($query);
        $this->applyAttributeFilter($query);
        $this->applyPromoFilter($query);

        return $query->count();
    }

    public function hasPriceFilter(): bool
    {
        return $this->precoMin !== null || $this->precoMax !== null;
    }

    public function hasSearchQuery(): bool
    {
        return trim($this->q) !== '';
    }

    /**
     * Apply search filter to query.
     * Uses ProductSearchService for consistent search across name, description, and SKU.
     *
     * @param  Builder<Product>  $query
     */
    private function applySearchFilter(Builder $query): void
    {
        if (trim($this->q) === '') {
            return;
        }

        app(ProductSearchService::class)->applySearch($query, $this->q);
    }

    /**
     * Apply price filter to query, considering promotional prices.
     *
     * @param  Builder<Product>  $query
     */
    private function applyPriceFilter(Builder $query): void
    {
        if ($this->precoMin === null && $this->precoMax === null) {
            return;
        }

        // Convert from reais to centavos
        $minCentavos = $this->precoMin !== null ? $this->precoMin * 100 : null;
        $maxCentavos = $this->precoMax !== null ? $this->precoMax * 100 : null;

        $now = now();

        $query->where(function ($q) use ($minCentavos, $maxCentavos, $now) {
            // Products with active sale price
            $q->where(function ($subQuery) use ($minCentavos, $maxCentavos, $now) {
                $subQuery->whereNotNull('sale_price')
                    ->where('sale_price', '>', 0)
                    ->where(function ($dateQuery) use ($now) {
                        $dateQuery->whereNull('sale_start_at')
                            ->orWhere('sale_start_at', '<=', $now);
                    })
                    ->where(function ($dateQuery) use ($now) {
                        $dateQuery->whereNull('sale_end_at')
                            ->orWhere('sale_end_at', '>=', $now);
                    });

                if ($minCentavos !== null) {
                    $subQuery->where('sale_price', '>=', $minCentavos);
                }

                if ($maxCentavos !== null) {
                    $subQuery->where('sale_price', '<=', $maxCentavos);
                }
            })
            // Products without active sale - use regular price
                ->orWhere(function ($subQuery) use ($minCentavos, $maxCentavos, $now) {
                    $subQuery->where(function ($noSaleQuery) use ($now) {
                        $noSaleQuery->whereNull('sale_price')
                            ->orWhere('sale_price', '<=', 0)
                            ->orWhere(function ($expiredQuery) use ($now) {
                                $expiredQuery->where('sale_end_at', '<', $now);
                            })
                            ->orWhere(function ($futureQuery) use ($now) {
                                $futureQuery->where('sale_start_at', '>', $now);
                            });
                    });

                    if ($minCentavos !== null) {
                        $subQuery->where('price', '>=', $minCentavos);
                    }

                    if ($maxCentavos !== null) {
                        $subQuery->where('price', '<=', $maxCentavos);
                    }
                });
        });
    }

    /**
     * Apply attribute filter to query.
     *
     * @param  Builder<Product>  $query
     */
    private function applyAttributeFilter(Builder $query): void
    {
        if (empty($this->atributos)) {
            return;
        }

        foreach ($this->atributos as $attributeSlug => $valueIds) {
            // Ensure valueIds is an array (Livewire may send boolean or other types)
            if (!is_array($valueIds) || empty($valueIds)) {
                continue;
            }

            // Convert string IDs to integers
            $valueIds = array_map('intval', $valueIds);

            // AND between different attributes, OR within same attribute values
            $query->whereHas('attributeValues', function ($q) use ($valueIds) {
                $q->whereIn('attribute_values.id', $valueIds);
            });
        }
    }

    /**
     * Apply promotion filter to query.
     *
     * @param  Builder<Product>  $query
     */
    private function applyPromoFilter(Builder $query): void
    {
        if (!$this->promocao) {
            return;
        }

        $query->onSale();
    }

    /**
     * Apply ordering to query.
     *
     * @param  Builder<Product>  $query
     */
    private function applyOrdering(Builder $query): void
    {
        $now = now();

        match ($this->ordenar) {
            'preco-asc'     => $query->orderByRaw($this->getEffectivePriceExpression($now) . ' ASC'),
            'preco-desc'    => $query->orderByRaw($this->getEffectivePriceExpression($now) . ' DESC'),
            'nome-asc'      => $query->orderBy('name', 'asc'),
            'mais-vendidos' => $query->withCount(['orderItems as sales_count'])
                ->orderByDesc('sales_count'),
            default => $query->latest(), // 'recentes'
        };
    }

    /**
     * Get SQL expression for effective price (considers promotional price).
     */
    private function getEffectivePriceExpression(\Illuminate\Support\Carbon $now): string
    {
        $nowFormatted = $now->format('Y-m-d H:i:s');

        return "CASE
            WHEN sale_price IS NOT NULL
                AND sale_price > 0
                AND (sale_start_at IS NULL OR sale_start_at <= '{$nowFormatted}')
                AND (sale_end_at IS NULL OR sale_end_at >= '{$nowFormatted}')
            THEN sale_price
            ELSE price
        END";
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->active()
            ->root()
            ->with(['children' => function ($query) {
                $query->active()->orderBy('position')->orderBy('name');
            }])
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available attributes based on active products.
     *
     * @return Collection<int, Attribute>
     */
    #[Computed]
    public function availableAttributes(): Collection
    {
        $activeStatus = \App\Domain\Catalog\Enums\ProductStatus::Active;

        return Attribute::query()
            ->whereHas('products', fn ($q) => $q->where('status', $activeStatus))
            ->with(['values' => fn ($query) => $query
                ->whereExists(fn ($subQuery) => $subQuery
                    ->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('product_attributes')
                    ->join('products', 'products.id', '=', 'product_attributes.product_id')
                    ->whereColumn('product_attributes.attribute_value_id', 'attribute_values.id')
                    ->where('products.status', $activeStatus))
                ->orderBy('position'),
            ])
            ->orderBy('position')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getCategoryIdsWithChildren(string $slug): \Illuminate\Support\Collection
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->first();

        if ($category === null) {
            return collect();
        }

        $ids = collect([$category->id]);

        $this->collectChildrenIds($category, $ids);

        return $ids;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     */
    private function collectChildrenIds(Category $category, \Illuminate\Support\Collection $ids): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Category> $children */
        $children = $category->children()->where('is_active', true)->get();

        foreach ($children as $child) {
            $ids->push($child->id);
            $this->collectChildrenIds($child, $ids);
        }
    }

    /**
     * Get suggested products for empty search results.
     *
     * @return Collection<int, Product>
     */
    #[Computed]
    public function suggestedProducts(): Collection
    {
        return Product::query()
            ->active()
            ->with(['images'])
            ->latest()
            ->limit(4)
            ->get();
    }

    /**
     * Get popular categories for empty search results.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function popularCategories(): Collection
    {
        return Category::query()
            ->active()
            ->root()
            ->orderBy('position')
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    private function getPageTitle(): string
    {
        $settings  = app(SettingsService::class);
        $storeName = $settings->get('general.store_name') ?: config('app.name');

        $searchTerm = trim($this->q);

        if ($searchTerm !== '') {
            return 'Busca: ' . $searchTerm . ' - ' . $storeName;
        }

        if ($this->categoria !== '') {
            $category = Category::query()->where('slug', $this->categoria)->first();

            if ($category !== null) {
                return $category->name . ' - ' . $storeName;
            }
        }

        return 'Produtos - ' . $storeName;
    }

    /**
     * Get meta description for SEO.
     */
    private function getMetaDescription(int $totalProducts): string
    {
        $settings  = app(SettingsService::class);
        $storeName = $settings->get('general.store_name') ?: config('app.name');

        $searchTerm = trim($this->q);

        if ($searchTerm !== '' && $totalProducts > 0) {
            return "Encontre {$totalProducts} produtos para \"{$searchTerm}\" em {$storeName}. Confira as melhores ofertas e precos.";
        }

        if ($searchTerm !== '' && $totalProducts === 0) {
            return "Nenhum resultado encontrado para \"{$searchTerm}\" em {$storeName}. Explore outras opcoes em nossa loja.";
        }

        if ($this->categoria !== '') {
            $category = Category::query()->where('slug', $this->categoria)->first();

            if ($category !== null) {
                return "Explore {$totalProducts} produtos em {$category->name}. Encontre as melhores ofertas em {$storeName}.";
            }
        }

        return "Explore nossa colecao de {$totalProducts} produtos em {$storeName}. Encontre as melhores ofertas e precos.";
    }

    /**
     * Get canonical URL for SEO.
     * Excludes pagination and sorting parameters to avoid duplicate content.
     */
    private function getCanonicalUrl(): string
    {
        $params = [];

        $searchTerm = trim($this->q);

        if ($searchTerm !== '') {
            $params['q'] = $searchTerm;
        }

        if ($this->categoria !== '') {
            $params['categoria'] = $this->categoria;
        }

        // Note: We exclude pagination, sorting, and filters from canonical
        // to consolidate SEO value on the main search/category page

        return route('search', $params);
    }

    /**
     * Get meta robots directive for SEO.
     * Returns noindex for pages that shouldn't be indexed.
     */
    private function getMetaRobots(int $totalProducts): ?string
    {
        // No results - don't index empty pages
        if ($totalProducts === 0) {
            return 'noindex, follow';
        }

        // Pages with filters applied - avoid duplicate content
        if ($this->hasActiveFilters()) {
            return 'noindex, follow';
        }

        // Indexable page
        return null;
    }

    /**
     * Check if any filters are currently active.
     */
    private function hasActiveFilters(): bool
    {
        // Price filter
        if ($this->precoMin !== null || $this->precoMax !== null) {
            return true;
        }

        // Attribute filter
        if ($this->hasAttributeFilter()) {
            return true;
        }

        // Promo filter
        if ($this->promocao) {
            return true;
        }

        // Custom ordering (not default)
        if ($this->ordenar !== 'recentes') {
            return true;
        }

        return false;
    }

    /**
     * Get contrast color for text on a given background color.
     */
    public function getContrastColor(?string $hexColor): string
    {
        if ($hexColor === null || $hexColor === '') {
            return 'dark';
        }

        $hex = ltrim($hexColor, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Using the relative luminance formula
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? 'dark' : 'light';
    }
}
