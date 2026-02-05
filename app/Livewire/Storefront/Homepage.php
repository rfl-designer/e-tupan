<?php

declare(strict_types = 1);

namespace App\Livewire\Storefront;

use App\Domain\Admin\Services\SettingsService;
use App\Domain\Catalog\Models\{Category, Product};
use App\Domain\Marketing\Models\Banner;
use App\Domain\Marketing\Services\BannerImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Homepage extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.homepage', [
            'banners'          => $this->getBanners(),
            'featuredProducts' => $this->getFeaturedProducts(),
            'saleProducts'     => $this->getSaleProducts(),
            'newProducts'      => $this->getNewProducts(),
            'categories'       => $this->getCategories(),
            'storeName'        => $this->getStoreName(),
        ])->layout('components.storefront-layout', [
            'title' => $this->getStoreName() . ' - Pagina Inicial',
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    private function getFeaturedProducts(): Collection
    {
        return Product::query()
            ->active()
            ->with(['categories', 'images'])
            ->inRandomOrder()
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function getSaleProducts(): Collection
    {
        return Product::query()
            ->active()
            ->onSale()
            ->with(['categories', 'images'])
            ->orderByRaw('(price - sale_price) DESC')
            ->limit(4)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function getNewProducts(): Collection
    {
        return Product::query()
            ->active()
            ->with(['categories', 'images'])
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    private function getCategories(): Collection
    {
        return Category::query()
            ->active()
            ->root()
            ->orderBy('position')
            ->limit(6)
            ->get();
    }

    private function getStoreName(): string
    {
        $settings = app(SettingsService::class);

        return $settings->get('general.store_name') ?: config('app.name');
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     title: string,
     *     alt: string,
     *     link: string|null,
     *     is_external: bool,
     *     desktop: array<string, string>,
     *     mobile: array<string, string>
     * }>
     */
    private function getBanners(): array
    {
        $imageService = app(BannerImageService::class);

        return Banner::query()
            ->displayable()
            ->ordered()
            ->get()
            ->map(function (Banner $banner) use ($imageService) {
                $desktopUrls = $imageService->getDesktopUrls($banner->image_desktop);
                $mobileUrls  = $banner->image_mobile !== null
                    ? $imageService->getMobileUrls($banner->image_mobile)
                    : $desktopUrls;

                return [
                    'id'          => (string) $banner->id,
                    'title'       => $banner->title,
                    'alt'         => $banner->alt_text ?? $banner->title,
                    'link'        => $banner->link,
                    'is_external' => $banner->isExternalLink(),
                    'desktop'     => $desktopUrls,
                    'mobile'      => $mobileUrls,
                ];
            })
            ->all();
    }
}
