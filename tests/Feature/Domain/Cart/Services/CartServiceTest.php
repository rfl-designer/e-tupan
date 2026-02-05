<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Exceptions\{InsufficientStockException, ProductNotAvailableException};
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Models\StockReservation;
use App\Models\User;

beforeEach(function () {
    $this->cartService = new CartService();
});

describe('CartService', function () {
    describe('getOrCreate', function () {
        it('creates new cart for user when none exists', function () {
            $user = User::factory()->create();

            $cart = $this->cartService->getOrCreate(userId: $user->id);

            expect($cart)->toBeInstanceOf(Cart::class)
                ->and($cart->user_id)->toBe($user->id)
                ->and($cart->session_id)->toBeNull()
                ->and($cart->status)->toBe(CartStatus::Active);
        });

        it('returns existing active cart for user', function () {
            $user         = User::factory()->create();
            $existingCart = Cart::factory()->forUser($user)->active()->create();

            $cart = $this->cartService->getOrCreate(userId: $user->id);

            expect($cart->id)->toBe($existingCart->id);
        });

        it('creates new cart for session when none exists', function () {
            $sessionId = 'test-session-123';

            $cart = $this->cartService->getOrCreate(sessionId: $sessionId);

            expect($cart)->toBeInstanceOf(Cart::class)
                ->and($cart->session_id)->toBe($sessionId)
                ->and($cart->user_id)->toBeNull()
                ->and($cart->status)->toBe(CartStatus::Active);
        });

        it('returns existing active cart for session', function () {
            $sessionId    = 'test-session-123';
            $existingCart = Cart::factory()->forSession($sessionId)->active()->create();

            $cart = $this->cartService->getOrCreate(sessionId: $sessionId);

            expect($cart->id)->toBe($existingCart->id);
        });

        it('throws exception when neither userId nor sessionId provided', function () {
            $this->cartService->getOrCreate();
        })->throws(\InvalidArgumentException::class);

        it('ignores abandoned cart and creates new one', function () {
            $user = User::factory()->create();
            Cart::factory()->forUser($user)->abandoned()->create();

            $cart = $this->cartService->getOrCreate(userId: $user->id);

            expect($cart->status)->toBe(CartStatus::Active);
            expect(Cart::forUser($user->id)->count())->toBe(2);
        });
    });

    describe('addItem', function () {
        it('adds simple product to cart', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 2);

            expect($item->product_id)->toBe($product->id)
                ->and($item->variant_id)->toBeNull()
                ->and($item->quantity)->toBe(2)
                ->and($item->unit_price)->toBe(5000);
        });

        it('adds variant to cart', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->variable()->active()->create();
            $variant = ProductVariant::factory()->create([
                'product_id'     => $product->id,
                'price'          => 6000,
                'stock_quantity' => 5,
            ]);

            $item = $this->cartService->addItem($cart, $product, 1, $variant);

            expect($item->product_id)->toBe($product->id)
                ->and($item->variant_id)->toBe($variant->id)
                ->and($item->quantity)->toBe(1)
                ->and($item->unit_price)->toBe(6000);
        });

        it('increases quantity if item already exists', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $this->cartService->addItem($cart, $product, 2);
            $item = $this->cartService->addItem($cart, $product, 3);

            expect($item->quantity)->toBe(5)
                ->and($cart->items()->count())->toBe(1);
        });

        it('creates stock reservation', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $this->cartService->addItem($cart, $product, 3);

            $reservation = StockReservation::forCart($cart->id)->first();
            expect($reservation)->not->toBeNull()
                ->and($reservation->quantity)->toBe(3)
                ->and($reservation->stockable_type)->toBe(Product::class)
                ->and($reservation->stockable_id)->toBe($product->id);
        });

        it('updates cart totals', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $this->cartService->addItem($cart, $product, 2);
            $cart->refresh();

            expect($cart->subtotal)->toBe(10000)
                ->and($cart->total)->toBe(10000);
        });

        it('throws exception for inactive product', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->inactive()->create();

            $this->cartService->addItem($cart, $product, 1);
        })->throws(ProductNotAvailableException::class);

        it('throws exception when insufficient stock', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 5,
                'manage_stock'   => true,
            ]);

            $this->cartService->addItem($cart, $product, 10);
        })->throws(InsufficientStockException::class);

        it('allows unlimited quantity for unmanaged stock', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->unlimitedStock()->create();

            $item = $this->cartService->addItem($cart, $product, 1000);

            expect($item->quantity)->toBe(1000);
        });

        it('captures sale price when product is on sale', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->onSale(4000)->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 1);

            expect($item->unit_price)->toBe(5000)
                ->and($item->sale_price)->toBe(4000);
        });
    });

    describe('updateItem', function () {
        it('updates item quantity', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item        = $this->cartService->addItem($cart, $product, 2);
            $updatedItem = $this->cartService->updateItem($item, 5);

            expect($updatedItem->quantity)->toBe(5);
        });

        it('removes item when quantity is zero', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 2);
            $this->cartService->updateItem($item, 0);

            expect($cart->items()->count())->toBe(0);
        });

        it('updates stock reservation', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 2);
            $this->cartService->updateItem($item, 5);

            $reservation = StockReservation::forCart($cart->id)->first();
            expect($reservation->quantity)->toBe(5);
        });

        it('updates cart totals', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 2);
            $this->cartService->updateItem($item, 4);
            $cart->refresh();

            expect($cart->subtotal)->toBe(20000)
                ->and($cart->total)->toBe(20000);
        });

        it('throws exception when increasing beyond stock', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 5,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 3);
            $this->cartService->updateItem($item, 10);
        })->throws(InsufficientStockException::class);
    });

    describe('removeItem', function () {
        it('removes item from cart', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 2);
            $this->cartService->removeItem($item);

            expect($cart->items()->count())->toBe(0);
        });

        it('releases stock reservation', function () {
            $user    = User::factory()->create();
            $cart    = $this->cartService->getOrCreate(userId: $user->id);
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item = $this->cartService->addItem($cart, $product, 2);
            $this->cartService->removeItem($item);

            expect(StockReservation::forCart($cart->id)->count())->toBe(0);
        });

        it('updates cart totals', function () {
            $user     = User::factory()->create();
            $cart     = $this->cartService->getOrCreate(userId: $user->id);
            $product1 = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            $product2 = Product::factory()->active()->create([
                'price'          => 3000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $item1 = $this->cartService->addItem($cart, $product1, 2);
            $this->cartService->addItem($cart, $product2, 1);
            $this->cartService->removeItem($item1);
            $cart->refresh();

            expect($cart->subtotal)->toBe(3000)
                ->and($cart->total)->toBe(3000);
        });
    });

    describe('clear', function () {
        it('removes all items from cart', function () {
            $user     = User::factory()->create();
            $cart     = $this->cartService->getOrCreate(userId: $user->id);
            $product1 = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            $product2 = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $this->cartService->addItem($cart, $product1, 2);
            $this->cartService->addItem($cart, $product2, 1);
            $this->cartService->clear($cart);

            expect($cart->items()->count())->toBe(0)
                ->and($cart->subtotal)->toBe(0)
                ->and($cart->total)->toBe(0);
        });

        it('releases all stock reservations', function () {
            $user     = User::factory()->create();
            $cart     = $this->cartService->getOrCreate(userId: $user->id);
            $product1 = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            $product2 = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $this->cartService->addItem($cart, $product1, 2);
            $this->cartService->addItem($cart, $product2, 1);
            $this->cartService->clear($cart);

            expect(StockReservation::forCart($cart->id)->count())->toBe(0);
        });

        it('resets shipping and coupon', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->withShipping()->withDiscount(500)->create();

            $this->cartService->clear($cart);

            expect($cart->shipping_cost)->toBeNull()
                ->and($cart->shipping_method)->toBeNull()
                ->and($cart->shipping_zipcode)->toBeNull()
                ->and($cart->coupon_id)->toBeNull();
        });
    });

    describe('stock reservation considers other carts', function () {
        it('considers reservations from other carts', function () {
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            // First cart reserves 7 units
            $user1 = User::factory()->create();
            $cart1 = $this->cartService->getOrCreate(userId: $user1->id);
            $this->cartService->addItem($cart1, $product, 7);

            // Second cart should only have 3 available
            $user2 = User::factory()->create();
            $cart2 = $this->cartService->getOrCreate(userId: $user2->id);

            $this->cartService->addItem($cart2, $product, 5);
        })->throws(InsufficientStockException::class);

        it('allows second cart to use remaining stock', function () {
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            // First cart reserves 7 units
            $user1 = User::factory()->create();
            $cart1 = $this->cartService->getOrCreate(userId: $user1->id);
            $this->cartService->addItem($cart1, $product, 7);

            // Second cart can get remaining 3
            $user2 = User::factory()->create();
            $cart2 = $this->cartService->getOrCreate(userId: $user2->id);
            $item  = $this->cartService->addItem($cart2, $product, 3);

            expect($item->quantity)->toBe(3);
        });
    });
});
