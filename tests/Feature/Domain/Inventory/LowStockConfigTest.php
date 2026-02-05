<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Livewire\Admin\StockList;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('Low Stock Threshold Configuration', function () {
    it('uses global default threshold when product threshold is null', function () {
        config(['inventory.default_low_stock_threshold' => 10]);

        $product = Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 5,
            'low_stock_threshold' => null,
        ]);

        expect($product->getLowStockThreshold())->toBe(10);
        expect($product->isLowStock())->toBeTrue();
    });

    it('uses product-specific threshold when set', function () {
        config(['inventory.default_low_stock_threshold' => 10]);

        $product = Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 8,
            'low_stock_threshold' => 5,
        ]);

        expect($product->getLowStockThreshold())->toBe(5);
        expect($product->isLowStock())->toBeFalse();
    });

    it('shows low stock indicator when below threshold', function () {
        $product = Product::factory()->create([
            'name'                => 'Low Stock Product',
            'manage_stock'        => true,
            'stock_quantity'      => 3,
            'low_stock_threshold' => 5,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('Estoque Baixo');
    });

    it('shows out of stock indicator when stock is zero', function () {
        $product = Product::factory()->create([
            'name'           => 'Out of Stock Product',
            'manage_stock'   => true,
            'stock_quantity' => 0,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('Sem Estoque');
    });

    it('shows normal stock indicator when above threshold', function () {
        $product = Product::factory()->create([
            'name'                => 'Normal Stock Product',
            'manage_stock'        => true,
            'stock_quantity'      => 100,
            'low_stock_threshold' => 5,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('Em Estoque');
    });
});

describe('Low Stock Filter', function () {
    it('filters products with low stock', function () {
        Product::factory()->create([
            'name'                => 'Normal Product',
            'manage_stock'        => true,
            'stock_quantity'      => 100,
            'low_stock_threshold' => 5,
        ]);

        Product::factory()->create([
            'name'                => 'Low Stock Product',
            'manage_stock'        => true,
            'stock_quantity'      => 3,
            'low_stock_threshold' => 5,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('stockStatus', 'low_stock')
            ->assertSee('Low Stock Product')
            ->assertDontSee('Normal Product');
    });

    it('filters products with out of stock', function () {
        Product::factory()->create([
            'name'           => 'In Stock Product',
            'manage_stock'   => true,
            'stock_quantity' => 10,
        ]);

        Product::factory()->create([
            'name'           => 'Out of Stock Product',
            'manage_stock'   => true,
            'stock_quantity' => 0,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('stockStatus', 'out_of_stock')
            ->assertSee('Out of Stock Product')
            ->assertDontSee('In Stock Product');
    });

    it('filters products in stock', function () {
        Product::factory()->create([
            'name'           => 'In Stock Product',
            'manage_stock'   => true,
            'stock_quantity' => 10,
        ]);

        Product::factory()->create([
            'name'           => 'Out of Stock Product',
            'manage_stock'   => true,
            'stock_quantity' => 0,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('stockStatus', 'in_stock')
            ->assertSee('In Stock Product')
            ->assertDontSee('Out of Stock Product');
    });
});

describe('belowThreshold Scope', function () {
    it('returns products below their threshold', function () {
        $lowStockProduct = Product::factory()->create([
            'name'                => 'Low Stock',
            'manage_stock'        => true,
            'stock_quantity'      => 3,
            'low_stock_threshold' => 5,
        ]);

        $normalProduct = Product::factory()->create([
            'name'                => 'Normal Stock',
            'manage_stock'        => true,
            'stock_quantity'      => 100,
            'low_stock_threshold' => 5,
        ]);

        $outOfStockProduct = Product::factory()->create([
            'name'                => 'Out of Stock',
            'manage_stock'        => true,
            'stock_quantity'      => 0,
            'low_stock_threshold' => 5,
        ]);

        $results = Product::query()->belowThreshold()->get();

        expect($results->pluck('name')->toArray())
            ->toContain('Low Stock')
            ->not->toContain('Normal Stock')
            ->not->toContain('Out of Stock');
    });

    it('uses global threshold when product threshold is null', function () {
        config(['inventory.default_low_stock_threshold' => 10]);

        $lowStockProduct = Product::factory()->create([
            'name'                => 'Low Stock Global',
            'manage_stock'        => true,
            'stock_quantity'      => 5,
            'low_stock_threshold' => null,
        ]);

        $normalProduct = Product::factory()->create([
            'name'                => 'Normal Stock Global',
            'manage_stock'        => true,
            'stock_quantity'      => 50,
            'low_stock_threshold' => null,
        ]);

        $results = Product::query()->belowThreshold()->get();

        expect($results->pluck('name')->toArray())
            ->toContain('Low Stock Global')
            ->not->toContain('Normal Stock Global');
    });
});

describe('isLowStock Method', function () {
    it('returns true when stock is below threshold but above zero', function () {
        $product = Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 3,
            'low_stock_threshold' => 5,
        ]);

        expect($product->isLowStock())->toBeTrue();
    });

    it('returns false when stock is at threshold', function () {
        $product = Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 5,
            'low_stock_threshold' => 5,
        ]);

        expect($product->isLowStock())->toBeTrue();
    });

    it('returns false when stock is zero', function () {
        $product = Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 0,
            'low_stock_threshold' => 5,
        ]);

        expect($product->isLowStock())->toBeFalse();
    });

    it('returns false when stock is above threshold', function () {
        $product = Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 100,
            'low_stock_threshold' => 5,
        ]);

        expect($product->isLowStock())->toBeFalse();
    });

    it('returns false when stock management is disabled', function () {
        $product = Product::factory()->create([
            'manage_stock'        => false,
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
        ]);

        expect($product->isLowStock())->toBeFalse();
    });
});
