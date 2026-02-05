<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Livewire\Admin\{LowStockWidget, RecentMovementsWidget, StockDashboard, StockStatsCard};
use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('StockController@dashboard', function () {
    it('requires authentication', function () {
        $this->get(route('admin.inventory.dashboard'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays stock dashboard page', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.inventory.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.inventory.dashboard');
    });
});

describe('StockStatsCard Component', function () {
    it('displays total SKUs with managed stock', function () {
        Product::factory()->count(5)->create(['manage_stock' => true]);
        Product::factory()->count(2)->create(['manage_stock' => false]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockStatsCard::class)
            ->assertSee('5')
            ->assertSee('SKUs Gerenciados');
    });

    it('displays products out of stock count', function () {
        Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 10]);
        Product::factory()->count(3)->create(['manage_stock' => true, 'stock_quantity' => 0]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockStatsCard::class)
            ->assertSee('3')
            ->assertSee('Sem Estoque');
    });

    it('displays products with low stock count', function () {
        Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 100,
            'low_stock_threshold' => 10,
        ]);
        Product::factory()->count(2)->create([
            'manage_stock'        => true,
            'stock_quantity'      => 5,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockStatsCard::class)
            ->assertSee('2')
            ->assertSee('Estoque Baixo');
    });

    it('displays total stock value in cost', function () {
        Product::factory()->create([
            'manage_stock'   => true,
            'stock_quantity' => 10,
            'cost'           => 5000, // R$ 50.00
        ]);
        Product::factory()->create([
            'manage_stock'   => true,
            'stock_quantity' => 5,
            'cost'           => 10000, // R$ 100.00
        ]);
        // Total: (10 * 50) + (5 * 100) = 500 + 500 = R$ 1000.00

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockStatsCard::class)
            ->assertSee('1.000,00')
            ->assertSee('Valor em Estoque');
    });

    it('handles products without cost correctly', function () {
        Product::factory()->create([
            'manage_stock'   => true,
            'stock_quantity' => 10,
            'cost'           => null,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockStatsCard::class)
            ->assertSee('0,00');
    });
});

describe('LowStockWidget Component', function () {
    it('displays products with low stock', function () {
        Product::factory()->create([
            'name'                => 'Low Stock Product',
            'sku'                 => 'LOW-001',
            'manage_stock'        => true,
            'stock_quantity'      => 3,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(LowStockWidget::class)
            ->assertSee('Low Stock Product')
            ->assertSee('LOW-001')
            ->assertSee('3');
    });

    it('displays products out of stock', function () {
        Product::factory()->create([
            'name'                => 'Out of Stock Product',
            'sku'                 => 'OUT-001',
            'manage_stock'        => true,
            'stock_quantity'      => 0,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(LowStockWidget::class)
            ->assertSee('Out of Stock Product')
            ->assertSee('OUT-001')
            ->assertSee('0');
    });

    it('limits display to top 10 items', function () {
        Product::factory()->count(15)->create([
            'manage_stock'        => true,
            'stock_quantity'      => 2,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(LowStockWidget::class);

        expect($component->viewData('lowStockItems')->count())->toBe(10);
    });

    it('orders by stock quantity ascending', function () {
        Product::factory()->create([
            'name'                => 'Product A',
            'manage_stock'        => true,
            'stock_quantity'      => 5,
            'low_stock_threshold' => 10,
        ]);
        Product::factory()->create([
            'name'                => 'Product B',
            'manage_stock'        => true,
            'stock_quantity'      => 0,
            'low_stock_threshold' => 10,
        ]);
        Product::factory()->create([
            'name'                => 'Product C',
            'manage_stock'        => true,
            'stock_quantity'      => 2,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(LowStockWidget::class);
        $items     = $component->viewData('lowStockItems');

        expect($items->first()->name)->toBe('Product B')
            ->and($items->last()->name)->toBe('Product A');
    });

    it('shows view all link to inventory page with filter', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(LowStockWidget::class)
            ->assertSee('Ver todos');
    });

    it('shows empty state when no low stock items', function () {
        Product::factory()->create([
            'manage_stock'        => true,
            'stock_quantity'      => 100,
            'low_stock_threshold' => 10,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(LowStockWidget::class)
            ->assertSee('Nenhum produto com estoque baixo');
    });
});

describe('RecentMovementsWidget Component', function () {
    it('displays recent stock movements', function () {
        $product = Product::factory()->create([
            'name'         => 'Test Product',
            'manage_stock' => true,
        ]);

        StockMovement::factory()->create([
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::ManualEntry,
            'quantity'        => 50,
            'quantity_before' => 0,
            'quantity_after'  => 50,
            'created_by'      => $this->admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(RecentMovementsWidget::class)
            ->assertSee('Test Product')
            ->assertSee('+50')
            ->assertSee('Entrada Manual');
    });

    it('displays movement type with correct badge color', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::ManualExit,
            'quantity'       => -10,
            'created_by'     => $this->admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(RecentMovementsWidget::class)
            ->assertSee('Saida Manual');
    });

    it('limits display to last 10 movements', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->count(15)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'created_by'     => $this->admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(RecentMovementsWidget::class);

        expect($component->viewData('movements')->count())->toBe(10);
    });

    it('orders by most recent first', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        $oldMovement = StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'notes'          => 'Old Movement',
            'created_at'     => now()->subDays(2),
            'created_by'     => $this->admin->id,
        ]);

        $newMovement = StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'notes'          => 'New Movement',
            'created_at'     => now(),
            'created_by'     => $this->admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(RecentMovementsWidget::class);

        expect($component->viewData('movements')->first()->notes)->toBe('New Movement');
    });

    it('shows view all link to movements page', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(RecentMovementsWidget::class)
            ->assertSee('Ver todas');
    });

    it('shows empty state when no movements', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(RecentMovementsWidget::class)
            ->assertSee('Nenhuma movimentacao registrada');
    });

    it('displays creator name when available', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'created_by'     => $this->admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(RecentMovementsWidget::class)
            ->assertSee($this->admin->name);
    });
});

describe('StockDashboard Component', function () {
    it('renders all widgets', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockDashboard::class)
            ->assertSeeLivewire(StockStatsCard::class)
            ->assertSeeLivewire(LowStockWidget::class)
            ->assertSeeLivewire(RecentMovementsWidget::class);
    });

    it('displays active reservations summary', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockReservation::factory()->count(3)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'expires_at'     => now()->addMinutes(30),
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockDashboard::class)
            ->assertSee('3')
            ->assertSee('Reservas Ativas');
    });

    it('displays quick action links', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockDashboard::class)
            ->assertSee('Gerenciar Estoque')
            ->assertSee('Ver Historico');
    });

    it('displays movements per day chart data', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        // Create movements over several days
        StockMovement::factory()->count(3)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'created_at'     => now()->subDays(1),
            'created_by'     => $this->admin->id,
        ]);
        StockMovement::factory()->count(5)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'created_at'     => now(),
            'created_by'     => $this->admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(StockDashboard::class);
        $chartData = $component->viewData('movementsChartData');

        expect($chartData)->toBeArray()
            ->and(count($chartData))->toBe(7);
    });
});
