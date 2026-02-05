<?php

declare(strict_types = 1);

namespace App\Livewire\Storefront;

use App\Domain\Catalog\Services\ProductSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SearchBox extends Component
{
    public string $query = '';

    public bool $showMobileSearch = false;

    /** @var array<int, array{id: int, name: string, slug: string, price: int, formatted_price: string, image: string|null}> */
    public array $suggestions = [];

    public function updatedQuery(): void
    {
        $this->loadSuggestions();
    }

    public function search(): void
    {
        $trimmedQuery = trim($this->query);

        if ($trimmedQuery === '') {
            return;
        }

        $this->redirect(route('search', ['q' => $trimmedQuery]), navigate: true);
    }

    public function selectSuggestion(string $slug): void
    {
        $this->redirect(route('products.show', $slug), navigate: true);
    }

    public function toggleMobileSearch(): void
    {
        $this->showMobileSearch = !$this->showMobileSearch;
    }

    #[Computed]
    public function showSuggestions(): bool
    {
        return count($this->suggestions) > 0;
    }

    private function loadSuggestions(): void
    {
        $trimmedQuery = trim($this->query);

        if (mb_strlen($trimmedQuery) < 2) {
            $this->suggestions = [];

            return;
        }

        $this->suggestions = app(ProductSearchService::class)
            ->search($trimmedQuery)
            ->with(['images' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->limit(5)
            ->get()
            ->map(fn ($product) => [
                'id'              => $product->id,
                'name'            => $product->name,
                'slug'            => $product->slug,
                'price'           => $product->getCurrentPrice(),
                'formatted_price' => Number::currency($product->getCurrentPrice() / 100, 'BRL', 'pt_BR'),
                'image'           => $product->images->first()?->getUrlForSize('thumb'),
            ])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.storefront.search-box');
    }
}
