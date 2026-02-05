<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\{Category, Product, ProductVariant};
use App\Domain\Inventory\Livewire\Admin\StockList;
use App\Domain\Inventory\Models\StockReservation;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('StockController@index', function () {
    it('requires authentication', function () {
        $this->get(route('admin.inventory.index'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays stock list page', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertViewIs('admin.inventory.index');
    });
});

describe('StockList Livewire Component', function () {
    it('displays products with stock information', function () {
        $product = Product::factory()->create([
            'name'           => 'Test Product',
            'sku'            => 'SKU-001',
            'stock_quantity' => 50,
            'manage_stock'   => true,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('Test Product')
            ->assertSee('SKU-001')
            ->assertSee('50');
    });

    it('displays product variants with stock information', function () {
        $product = Product::factory()->create([
            'name'         => 'Variable Product',
            'type'         => 'variable',
            'manage_stock' => true,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'sku'            => 'VAR-001',
            'stock_quantity' => 25,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('VAR-001')
            ->assertSee('25');
    });

    it('shows available quantity considering reservations', function () {
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'manage_stock'   => true,
        ]);

        StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 30,
            'expires_at'     => now()->addMinutes(30),
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('100') // Total stock
            ->assertSee('30')  // Reserved
            ->assertSee('70'); // Available
    });

    it('can search by product name', function () {
        Product::factory()->create(['name' => 'Apple iPhone', 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Samsung Galaxy', 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('search', 'iPhone')
            ->assertSee('Apple iPhone')
            ->assertDontSee('Samsung Galaxy');
    });

    it('can search by SKU', function () {
        Product::factory()->create(['name' => 'Product A', 'sku' => 'SKU-APPLE', 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Product B', 'sku' => 'SKU-BANANA', 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('search', 'APPLE')
            ->assertSee('Product A')
            ->assertDontSee('Product B');
    });

    it('can filter by category', function () {
        $category1 = Category::factory()->create(['name' => 'Electronics']);
        $category2 = Category::factory()->create(['name' => 'Clothing']);

        $product1 = Product::factory()->create(['name' => 'Phone', 'manage_stock' => true]);
        $product1->categories()->attach($category1);

        $product2 = Product::factory()->create(['name' => 'T-Shirt', 'manage_stock' => true]);
        $product2->categories()->attach($category2);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('category', $category1->id)
            ->assertSee('Phone')
            ->assertDontSee('T-Shirt');
    });

    it('can filter by stock status - out of stock', function () {
        Product::factory()->create(['name' => 'In Stock Product', 'stock_quantity' => 10, 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Out of Stock Product', 'stock_quantity' => 0, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('stockStatus', 'out_of_stock')
            ->assertSee('Out of Stock Product')
            ->assertDontSee('In Stock Product');
    });

    it('can filter by stock status - low stock', function () {
        Product::factory()->create([
            'name'                => 'Normal Stock Product',
            'stock_quantity'      => 100,
            'manage_stock'        => true,
            'low_stock_threshold' => 10,
        ]);
        Product::factory()->create([
            'name'                => 'Low Stock Product',
            'stock_quantity'      => 5,
            'manage_stock'        => true,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('stockStatus', 'low_stock')
            ->assertSee('Low Stock Product')
            ->assertDontSee('Normal Stock Product');
    });

    it('can sort by quantity ascending', function () {
        Product::factory()->create(['name' => 'Product A', 'stock_quantity' => 50, 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Product B', 'stock_quantity' => 10, 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Product C', 'stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        // Default sort is already stock_quantity ASC, clicking toggles to DESC then back to ASC
        $component = Livewire::test(StockList::class)
            ->call('sortBy', 'stock_quantity') // Toggles to DESC
            ->call('sortBy', 'stock_quantity'); // Toggles back to ASC

        // First item should be Product B (lowest stock)
        expect($component->viewData('stockItems')->first()->name)->toBe('Product B');
    });

    it('can sort by quantity descending', function () {
        Product::factory()->create(['name' => 'Product A', 'stock_quantity' => 50, 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Product B', 'stock_quantity' => 10, 'manage_stock' => true]);
        Product::factory()->create(['name' => 'Product C', 'stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        // Default sort is stock_quantity ASC, first click toggles to DESC
        $component = Livewire::test(StockList::class)
            ->call('sortBy', 'stock_quantity');

        // First item should be Product C (highest stock)
        expect($component->viewData('stockItems')->first()->name)->toBe('Product C');
    });

    it('displays low stock badge for products below threshold', function () {
        Product::factory()->create([
            'name'                => 'Low Stock Product',
            'stock_quantity'      => 3,
            'manage_stock'        => true,
            'low_stock_threshold' => 5,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('Estoque Baixo');
    });

    it('displays out of stock badge for products with zero stock', function () {
        Product::factory()->create([
            'name'           => 'Out of Stock Product',
            'stock_quantity' => 0,
            'manage_stock'   => true,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->assertSee('Sem Estoque');
    });

    it('paginates results', function () {
        Product::factory()->count(20)->create(['manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(StockList::class);

        expect($component->viewData('stockItems')->count())->toBe(15);
    });

    it('can clear filters', function () {
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'Test Product', 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('search', 'test')
            ->set('category', $category->id)
            ->set('stockStatus', 'low_stock')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('category', '')
            ->assertSet('stockStatus', '');
    });
});
