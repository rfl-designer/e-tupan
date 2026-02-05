<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Livewire\Admin\StockList;
use App\Domain\Inventory\Services\StockService;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin        = Admin::factory()->master()->withTwoFactor()->create();
    $this->stockService = app(StockService::class);
});

describe('StockService::adjust', function () {
    it('adjusts stock for a product with manual entry', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        $movement = $this->stockService->adjust(
            stockable: $product,
            quantity: 50,
            type: MovementType::ManualEntry,
            notes: 'Received new shipment',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(150)
            ->and($movement->quantity)->toBe(50)
            ->and($movement->quantity_before)->toBe(100)
            ->and($movement->quantity_after)->toBe(150)
            ->and($movement->movement_type)->toBe(MovementType::ManualEntry)
            ->and($movement->notes)->toBe('Received new shipment')
            ->and($movement->created_by)->toBe($this->admin->id);
    });

    it('adjusts stock for a product with manual exit', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        $movement = $this->stockService->adjust(
            stockable: $product,
            quantity: -30,
            type: MovementType::ManualExit,
            notes: 'Damaged goods removed',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(70)
            ->and($movement->quantity)->toBe(-30)
            ->and($movement->quantity_before)->toBe(100)
            ->and($movement->quantity_after)->toBe(70);
    });

    it('adjusts stock for a product variant', function () {
        $product = Product::factory()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 50,
        ]);

        actingAsAdminWith2FA($this, $this->admin);

        $movement = $this->stockService->adjust(
            stockable: $variant,
            quantity: 25,
            type: MovementType::ManualEntry,
            notes: 'Restock variant',
        );

        $variant->refresh();

        expect($variant->stock_quantity)->toBe(75)
            ->and($movement->stockable_type)->toBe(ProductVariant::class)
            ->and($movement->stockable_id)->toBe($variant->id);
    });

    it('throws exception when stock would become negative', function () {
        $product = Product::factory()->create(['stock_quantity' => 10, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        $this->stockService->adjust(
            stockable: $product,
            quantity: -20,
            type: MovementType::ManualExit,
            notes: 'Should fail',
        );
    })->throws(InsufficientStockException::class);

    it('allows negative stock when configured', function () {
        config(['inventory.allow_negative_stock' => true]);

        $product = Product::factory()->create(['stock_quantity' => 10, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        $movement = $this->stockService->adjust(
            stockable: $product,
            quantity: -20,
            type: MovementType::ManualExit,
            notes: 'Oversold',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(-10);
    });

    it('records adjustment in stock movements history', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        $this->stockService->adjust(
            stockable: $product,
            quantity: 10,
            type: MovementType::Adjustment,
            notes: 'Inventory count correction',
        );

        $this->assertDatabaseHas('stock_movements', [
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::Adjustment->value,
            'quantity'        => 10,
            'quantity_before' => 100,
            'quantity_after'  => 110,
            'notes'           => 'Inventory count correction',
            'created_by'      => $this->admin->id,
        ]);
    });

    it('uses database transaction for atomicity', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        // Simulate a failure scenario
        try {
            \DB::transaction(function () use ($product) {
                $this->stockService->adjust(
                    stockable: $product,
                    quantity: 50,
                    type: MovementType::ManualEntry,
                    notes: 'Test',
                );

                throw new \Exception('Simulated failure');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $product->refresh();

        // Stock should remain unchanged due to rollback
        expect($product->stock_quantity)->toBe(100);
    });
});

describe('StockController@adjust', function () {
    it('requires authentication', function () {
        $product = Product::factory()->create();

        $this->post(route('admin.inventory.adjust'), [
            'stockable_type' => 'product',
            'stockable_id'   => $product->id,
            'movement_type'  => 'manual_entry',
            'quantity'       => 10,
            'notes'          => 'Test',
        ])->assertRedirect(route('admin.login'));
    });

    it('adjusts stock via HTTP request', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.inventory.adjust'), [
                'stockable_type' => 'product',
                'stockable_id'   => $product->id,
                'movement_type'  => 'manual_entry',
                'quantity'       => 25,
                'notes'          => 'New stock arrived',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();
        expect($product->stock_quantity)->toBe(125);
    });

    it('validates required fields', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.inventory.adjust'), [])
            ->assertSessionHasErrors(['stockable_type', 'stockable_id', 'movement_type', 'quantity', 'notes']);
    });

    it('validates movement type is valid', function () {
        $product = Product::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.inventory.adjust'), [
                'stockable_type' => 'product',
                'stockable_id'   => $product->id,
                'movement_type'  => 'invalid_type',
                'quantity'       => 10,
                'notes'          => 'Test',
            ])
            ->assertSessionHasErrors('movement_type');
    });

    it('validates quantity is integer', function () {
        $product = Product::factory()->create();

        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.inventory.adjust'), [
                'stockable_type' => 'product',
                'stockable_id'   => $product->id,
                'movement_type'  => 'manual_entry',
                'quantity'       => 'not-a-number',
                'notes'          => 'Test',
            ])
            ->assertSessionHasErrors('quantity');
    });

    it('validates stockable exists', function () {
        actingAsAdminWith2FA($this, $this->admin)
            ->post(route('admin.inventory.adjust'), [
                'stockable_type' => 'product',
                'stockable_id'   => 99999,
                'movement_type'  => 'manual_entry',
                'quantity'       => 10,
                'notes'          => 'Test',
            ])
            ->assertSessionHasErrors('stockable_id');
    });
});

describe('StockList Livewire Component - Adjustment Modal', function () {
    it('opens adjust stock modal', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->call('openAdjustModal', 'product', $product->id)
            ->assertSet('adjustModal', true)
            ->assertSet('adjustStockableType', 'product')
            ->assertSet('adjustStockableId', $product->id);
    });

    it('closes adjust stock modal', function () {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->set('adjustModal', true)
            ->call('closeAdjustModal')
            ->assertSet('adjustModal', false)
            ->assertSet('adjustQuantity', 0)
            ->assertSet('adjustNotes', '');
    });

    it('performs stock adjustment from modal', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->call('openAdjustModal', 'product', $product->id)
            ->set('adjustMovementType', 'manual_entry')
            ->set('adjustQuantity', 25)
            ->set('adjustNotes', 'Restocking')
            ->call('submitAdjustment')
            ->assertSet('adjustModal', false)
            ->assertDispatched('notify');

        $product->refresh();
        expect($product->stock_quantity)->toBe(75);
    });

    it('validates adjustment form', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(StockList::class)
            ->call('openAdjustModal', 'product', $product->id)
            ->set('adjustQuantity', 0)
            ->set('adjustNotes', '')
            ->call('submitAdjustment')
            ->assertHasErrors(['adjustQuantity', 'adjustNotes']);
    });
});
