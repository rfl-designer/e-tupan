<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Models\StockMovement;

beforeEach(function () {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
});

describe('StockMovement Model', function () {
    it('belongs to a stockable (Product)', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $movement = StockMovement::factory()->create([
            'stockable_type'  => Product::class,
            'stockable_id'    => $product->id,
            'movement_type'   => MovementType::ManualEntry,
            'quantity'        => 10,
            'quantity_before' => 90,
            'quantity_after'  => 100,
        ]);

        expect($movement->stockable)->toBeInstanceOf(Product::class)
            ->and($movement->stockable->id)->toBe($product->id);
    });

    it('belongs to a stockable (ProductVariant)', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 50,
        ]);

        $movement = StockMovement::factory()->create([
            'stockable_type'  => ProductVariant::class,
            'stockable_id'    => $variant->id,
            'movement_type'   => MovementType::ManualEntry,
            'quantity'        => 5,
            'quantity_before' => 45,
            'quantity_after'  => 50,
        ]);

        expect($movement->stockable)->toBeInstanceOf(ProductVariant::class)
            ->and($movement->stockable->id)->toBe($variant->id);
    });

    it('can belong to a creator (admin)', function () {
        $product = Product::factory()->create();

        $movement = StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'created_by'     => $this->admin->id,
        ]);

        expect($movement->creator)->toBeInstanceOf(Admin::class)
            ->and($movement->creator->id)->toBe($this->admin->id);
    });

    it('casts movement_type to enum', function () {
        $product = Product::factory()->create();

        $movement = StockMovement::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::Sale,
        ]);

        expect($movement->movement_type)->toBe(MovementType::Sale);
    });

    it('can filter by movement type', function () {
        $product = Product::factory()->create();

        StockMovement::factory()->count(3)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::ManualEntry,
        ]);

        StockMovement::factory()->count(2)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'movement_type'  => MovementType::Sale,
        ]);

        expect(StockMovement::query()->where('movement_type', MovementType::ManualEntry)->count())->toBe(3)
            ->and(StockMovement::query()->where('movement_type', MovementType::Sale)->count())->toBe(2);
    });

    it('can filter by stockable', function () {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        StockMovement::factory()->count(3)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product1->id,
        ]);

        StockMovement::factory()->count(2)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product2->id,
        ]);

        expect(StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product1->id)
            ->count())->toBe(3);
    });
});

describe('MovementType Enum', function () {
    it('has correct labels', function () {
        expect(MovementType::ManualEntry->label())->toBe('Entrada Manual')
            ->and(MovementType::ManualExit->label())->toBe('Saida Manual')
            ->and(MovementType::Adjustment->label())->toBe('Ajuste de Inventario')
            ->and(MovementType::Sale->label())->toBe('Venda')
            ->and(MovementType::Refund->label())->toBe('Estorno')
            ->and(MovementType::Reservation->label())->toBe('Reserva de Carrinho')
            ->and(MovementType::ReservationRelease->label())->toBe('Liberacao de Reserva');
    });

    it('identifies positive movements', function () {
        expect(MovementType::ManualEntry->isPositive())->toBeTrue()
            ->and(MovementType::Refund->isPositive())->toBeTrue()
            ->and(MovementType::ReservationRelease->isPositive())->toBeTrue()
            ->and(MovementType::ManualExit->isPositive())->toBeFalse()
            ->and(MovementType::Sale->isPositive())->toBeFalse();
    });

    it('has correct color for display', function () {
        expect(MovementType::ManualEntry->color())->toBe('green')
            ->and(MovementType::ManualExit->color())->toBe('red')
            ->and(MovementType::Adjustment->color())->toBe('amber')
            ->and(MovementType::Sale->color())->toBe('blue')
            ->and(MovementType::Refund->color())->toBe('purple');
    });
});
