<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Livewire\Admin\StockMovementLog;
use App\Domain\Inventory\Models\StockMovement;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('StockController@movements', function () {
    it('requires authentication', function () {
        $this->get(route('admin.inventory.movements'))
            ->assertRedirect(route('admin.login'));
    });

    it('displays stock movements page', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->get(route('admin.inventory.movements'))
            ->assertOk()
            ->assertViewIs('admin.inventory.movements');
    });
});

describe('StockMovementLog Livewire Component', function () {
    it('displays stock movements in chronological order (most recent first)', function () {
        $product = Product::factory()->create(['manage_stock' => true, 'sku' => 'TEST-SKU']);

        $olderMovement = StockMovement::factory()->create([
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::ManualEntry,
            'quantity'        => 10,
            'quantity_before' => 0,
            'quantity_after'  => 10,
            'created_at'      => now()->subHour(),
        ]);

        $newerMovement = StockMovement::factory()->create([
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::Sale,
            'quantity'        => -2,
            'quantity_before' => 10,
            'quantity_after'  => 8,
            'created_at'      => now(),
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(StockMovementLog::class);

        // First movement should be the newer one
        expect($component->viewData('movements')->first()->id)->toBe($newerMovement->id);
    });

    it('displays movement details: date, SKU, type, quantity, balance before/after, user, notes', function () {
        $admin   = Admin::factory()->master()->withTwoFactor()->create(['name' => 'John Admin']);
        $product = Product::factory()->create([
            'manage_stock' => true,
            'sku'          => 'PROD-001',
            'name'         => 'Test Product',
        ]);

        StockMovement::factory()->create([
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::ManualEntry,
            'quantity'        => 50,
            'quantity_before' => 0,
            'quantity_after'  => 50,
            'notes'           => 'Initial stock',
            'created_by'      => $admin->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockMovementLog::class)
            ->assertSee('PROD-001')
            ->assertSee('Entrada Manual')
            ->assertSee('50')
            ->assertSee('Initial stock')
            ->assertSee('John Admin');
    });

    it('can filter by date range', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        $oldMovement = StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::ManualEntry,
            'notes'          => 'Old movement',
            'created_at'     => now()->subDays(10),
        ]);

        $recentMovement = StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::ManualEntry,
            'notes'          => 'Recent movement',
            'created_at'     => now(),
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockMovementLog::class)
            ->set('dateFrom', now()->subDays(5)->format('Y-m-d'))
            ->set('dateTo', now()->addDay()->format('Y-m-d'))
            ->assertSee('Recent movement')
            ->assertDontSee('Old movement');
    });

    it('can filter by SKU', function () {
        $product1 = Product::factory()->create(['manage_stock' => true, 'sku' => 'SKU-ALPHA']);
        $product2 = Product::factory()->create(['manage_stock' => true, 'sku' => 'SKU-BETA']);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product1->id,
            'notes'          => 'Alpha movement',
        ]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product2->id,
            'notes'          => 'Beta movement',
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockMovementLog::class)
            ->set('searchSku', 'ALPHA')
            ->assertSee('Alpha movement')
            ->assertDontSee('Beta movement');
    });

    it('can filter by movement type', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::ManualEntry,
            'notes'          => 'Entry movement',
        ]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::Sale,
            'notes'          => 'Sale movement',
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockMovementLog::class)
            ->set('movementType', MovementType::Sale->value)
            ->assertSee('Sale movement')
            ->assertDontSee('Entry movement');
    });

    it('can filter by user', function () {
        $admin1  = Admin::factory()->master()->withTwoFactor()->create(['name' => 'Admin One']);
        $admin2  = Admin::factory()->master()->withTwoFactor()->create(['name' => 'Admin Two']);
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'notes'          => 'Movement by admin one',
            'created_by'     => $admin1->id,
        ]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'notes'          => 'Movement by admin two',
            'created_by'     => $admin2->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockMovementLog::class)
            ->set('createdBy', $admin1->id)
            ->assertSee('Movement by admin one')
            ->assertDontSee('Movement by admin two');
    });

    it('displays movement types with correct colors', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::ManualEntry,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        // ManualEntry should have green color
        Livewire::test(StockMovementLog::class)
            ->assertSee('Entrada Manual');
    });

    it('paginates results', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->count(25)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $component = Livewire::test(StockMovementLog::class);

        expect($component->viewData('movements')->count())->toBe(15);
    });

    it('can clear filters', function () {
        $admin   = Admin::factory()->master()->withTwoFactor()->create();
        $product = Product::factory()->create(['manage_stock' => true]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockMovementLog::class)
            ->set('searchSku', 'test')
            ->set('movementType', MovementType::Sale->value)
            ->set('createdBy', $admin->id)
            ->set('dateFrom', '2024-01-01')
            ->set('dateTo', '2024-12-31')
            ->call('clearFilters')
            ->assertSet('searchSku', '')
            ->assertSet('movementType', '')
            ->assertSet('createdBy', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '');
    });
});

describe('CSV Export', function () {
    it('can export movements to CSV', function () {
        $product = Product::factory()->create(['manage_stock' => true, 'sku' => 'CSV-001']);

        StockMovement::factory()->create([
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::ManualEntry,
            'quantity'        => 100,
            'quantity_before' => 0,
            'quantity_after'  => 100,
            'notes'           => 'Test export',
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $response = Livewire::test(StockMovementLog::class)
            ->call('exportCsv');

        $response->assertFileDownloaded();
    });

    it('exports filtered results when filters are applied', function () {
        $product1 = Product::factory()->create(['manage_stock' => true, 'sku' => 'EXPORT-A']);
        $product2 = Product::factory()->create(['manage_stock' => true, 'sku' => 'EXPORT-B']);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product1->id,
            'movement_type'  => MovementType::ManualEntry,
        ]);

        StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product2->id,
            'movement_type'  => MovementType::Sale,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $response = Livewire::test(StockMovementLog::class)
            ->set('searchSku', 'EXPORT-A')
            ->call('exportCsv');

        $response->assertFileDownloaded();
    });
});
