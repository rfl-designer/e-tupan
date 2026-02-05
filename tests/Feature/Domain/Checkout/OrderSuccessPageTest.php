<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Livewire\OrderConfirmation;
use App\Domain\Checkout\Models\{Order, OrderItem, Payment};
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $this->product = Product::factory()->create([
        'name'  => 'Test Product',
        'price' => 5000, // R$ 50.00
    ]);

    $this->order = Order::factory()->for($this->user)->create([
        'status'                  => OrderStatus::Pending,
        'payment_status'          => PaymentStatus::Approved,
        'subtotal'                => 10000,
        'shipping_cost'           => 2500,
        'discount'                => 0,
        'total'                   => 12500,
        'shipping_recipient_name' => 'John Doe',
        'shipping_zipcode'        => '01310-100',
        'shipping_street'         => 'Av Paulista',
        'shipping_number'         => '1000',
        'shipping_neighborhood'   => 'Bela Vista',
        'shipping_city'           => 'Sao Paulo',
        'shipping_state'          => 'SP',
        'shipping_method'         => 'sedex',
        'shipping_carrier'        => 'Correios',
        'shipping_days'           => 3,
    ]);

    OrderItem::factory()->for($this->order)->create([
        'product_id'   => $this->product->id,
        'product_name' => 'Test Product',
        'quantity'     => 2,
        'unit_price'   => 5000,
        'subtotal'     => 10000,
    ]);
});

describe('Order Confirmation Page Access', function () {
    it('allows order owner to access success page', function () {
        $this->actingAs($this->user)
            ->get(route('checkout.success', $this->order))
            ->assertOk();
    });

    it('denies access to other authenticated users', function () {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('checkout.success', $this->order))
            ->assertForbidden();
    });

    it('allows guest access with valid token', function () {
        $guestOrder = Order::factory()->create([
            'user_id'      => null,
            'guest_email'  => 'guest@example.com',
            'guest_name'   => 'Guest User',
            'access_token' => 'valid-token-123',
        ]);

        $this->get(route('checkout.success', ['order' => $guestOrder, 'token' => 'valid-token-123']))
            ->assertOk();
    });

    it('denies guest access with invalid token', function () {
        $guestOrder = Order::factory()->create([
            'user_id'      => null,
            'guest_email'  => 'guest@example.com',
            'guest_name'   => 'Guest User',
            'access_token' => 'valid-token-123',
        ]);

        $this->get(route('checkout.success', ['order' => $guestOrder, 'token' => 'invalid-token']))
            ->assertForbidden();
    });
});

describe('Order Confirmation Component', function () {
    it('displays order number', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee($this->order->order_number);
    });

    it('displays payment status', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Aprovado');
    });

    it('displays pending payment status for Pix', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'      => PaymentMethod::Pix,
            'status'      => PaymentStatus::Pending,
            'pix_qr_code' => 'base64-qr-code-data',
            'pix_code'    => '00020126580014BR.GOV.BCB.PIX',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Aguardando pagamento')
            ->assertSee('QR Code');
    });

    it('displays pending payment status for Bank Slip', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'            => PaymentMethod::BankSlip,
            'status'            => PaymentStatus::Pending,
            'bank_slip_barcode' => '23793.12345 12345.678901 12345.678901 1 12340000012500',
            'bank_slip_url'     => 'https://example.com/boleto.pdf',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Aguardando pagamento')
            ->assertSee('Boleto');
    });

    it('displays order items', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Test Product')
            ->assertSee('2'); // quantity
    });

    it('displays shipping address', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Av Paulista')
            ->assertSee('1000')
            ->assertSee('Bela Vista')
            ->assertSee('Sao Paulo');
    });

    it('displays order totals', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('R$ 100,00') // subtotal
            ->assertSee('R$ 25,00')  // shipping
            ->assertSee('R$ 125,00'); // total
    });

    it('displays delivery estimate', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('3 dias uteis');
    });

    it('displays discount when applied', function () {
        $this->order->update([
            'discount'    => 1000,
            'total'       => 11500,
            'coupon_code' => 'DESCONTO10',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('DESCONTO10')
            ->assertSee('R$ 10,00'); // discount
    });
});

describe('Order Confirmation Actions', function () {
    it('shows view my orders button for authenticated users', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Ver meus pedidos');
    });

    it('shows continue shopping button', function () {
        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Continuar comprando');
    });

    it('hides view my orders button for guest users', function () {
        $guestOrder = Order::factory()->create([
            'user_id'      => null,
            'guest_email'  => 'guest@example.com',
            'guest_name'   => 'Guest User',
            'access_token' => 'valid-token',
        ]);

        Livewire::test(OrderConfirmation::class, [
            'order'   => $guestOrder,
            'isGuest' => true,
        ])
            ->assertDontSee('Ver meus pedidos')
            ->assertSee('Continuar comprando');
    });

    it('shows create account offer for guest users', function () {
        $guestOrder = Order::factory()->create([
            'user_id'      => null,
            'guest_email'  => 'guest@example.com',
            'guest_name'   => 'Guest User',
            'access_token' => 'valid-token',
        ]);

        Livewire::test(OrderConfirmation::class, [
            'order'   => $guestOrder,
            'isGuest' => true,
        ])
            ->assertSee('Criar conta');
    });
});

describe('Pix Payment Display', function () {
    it('displays Pix QR Code when payment is pending', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'      => PaymentMethod::Pix,
            'status'      => PaymentStatus::Pending,
            'pix_qr_code' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'pix_code'    => '00020126580014BR.GOV.BCB.PIX',
            'expires_at'  => now()->addMinutes(30),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order]);

        $component->assertSee('Pix')
            ->assertSee('00020126580014BR.GOV.BCB.PIX');
    });

    it('shows copy button for Pix code', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'   => PaymentMethod::Pix,
            'status'   => PaymentStatus::Pending,
            'pix_code' => '00020126580014BR.GOV.BCB.PIX',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Copiar');
    });

    it('shows Pix expiration time', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'     => PaymentMethod::Pix,
            'status'     => PaymentStatus::Pending,
            'pix_code'   => '00020126580014BR.GOV.BCB.PIX',
            'expires_at' => now()->addMinutes(30),
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Valido por');
    });
});

describe('Bank Slip Payment Display', function () {
    it('displays Bank Slip barcode when payment is pending', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'            => PaymentMethod::BankSlip,
            'status'            => PaymentStatus::Pending,
            'bank_slip_barcode' => '23793.12345 12345.678901 12345.678901 1 12340000012500',
            'bank_slip_url'     => 'https://example.com/boleto.pdf',
            'expires_at'        => now()->addDays(3),
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Boleto')
            ->assertSee('23793.12345');
    });

    it('shows copy button for barcode', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'            => PaymentMethod::BankSlip,
            'status'            => PaymentStatus::Pending,
            'bank_slip_barcode' => '23793.12345 12345.678901 12345.678901 1 12340000012500',
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Copiar');
    });

    it('shows download boleto button', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'            => PaymentMethod::BankSlip,
            'status'            => PaymentStatus::Pending,
            'bank_slip_barcode' => '23793.12345 12345.678901 12345.678901 1 12340000012500',
            'bank_slip_url'     => 'https://example.com/boleto.pdf',
        ]);

        $this->order->refresh();

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Baixar boleto');
    });

    it('shows bank slip due date', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'     => PaymentMethod::BankSlip,
            'status'     => PaymentStatus::Pending,
            'expires_at' => now()->addDays(3),
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderConfirmation::class, ['order' => $this->order])
            ->assertSee('Vencimento');
    });
});
