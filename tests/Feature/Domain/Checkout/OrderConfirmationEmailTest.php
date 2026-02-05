<?php

declare(strict_types = 1);

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Listeners\LogFailedNotification;
use App\Domain\Admin\Listeners\LogSentNotification;
use App\Domain\Admin\Models\EmailLog;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Listeners\SendOrderConfirmationEmail;
use App\Domain\Checkout\Models\{Order, OrderItem, Payment};
use App\Domain\Checkout\Notifications\OrderConfirmationNotification;
use App\Models\User;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->user = User::factory()->create([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $this->product = Product::factory()->create([
        'name'  => 'Test Product',
        'price' => 5000,
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

describe('Order Confirmation Email', function () {
    it('sends confirmation email to authenticated user', function () {
        $notification = new OrderConfirmationNotification($this->order);

        $this->user->notify($notification);

        Notification::assertSentTo(
            $this->user,
            OrderConfirmationNotification::class,
        );
    });

    it('sends confirmation email to guest user', function () {
        // Reset notification fake to start fresh
        Notification::fake();

        $guestOrder = Order::factory()->create([
            'user_id'     => null,
            'guest_email' => 'guest@example.com',
            'guest_name'  => 'Guest User',
        ]);

        OrderItem::factory()->for($guestOrder)->create([
            'product_id'   => $this->product->id,
            'product_name' => 'Test Product',
            'quantity'     => 1,
            'unit_price'   => 5000,
            'subtotal'     => 5000,
        ]);

        // The notification is automatically sent via OrderCreated event
        Notification::assertSentOnDemand(
            OrderConfirmationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'guest@example.com',
        );
    });

    it('includes order number in email subject', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);

        expect($mail->subject)->toContain($this->order->order_number);
    });

    it('includes store name in email subject', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);

        // Default store name from config
        $storeName = config('app.name');
        expect($mail->subject)->toContain($storeName);
        expect($mail->subject)->toMatch('/Pedido #.+ confirmado - .+/');
    });

    it('displays order number prominently in email body', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);
        $content = (string) $mail->render();

        // Order number should appear in highlighted section
        expect(str_contains($content, $this->order->order_number))->toBeTrue();
        expect(str_contains($content, 'Numero do Pedido'))->toBeTrue();
    });

    it('displays order date and time in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);
        $content = (string) $mail->render();

        expect(str_contains($content, $this->order->placed_at->format('d/m/Y')))->toBeTrue();
        expect(str_contains($content, $this->order->placed_at->format('H:i')))->toBeTrue();
        expect(str_contains($content, 'Data do Pedido'))->toBeTrue();
    });

    it('displays payment status in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);
        $content = (string) $mail->render();

        expect(str_contains($content, 'Status do Pagamento'))->toBeTrue();
        expect(str_contains($content, $this->order->payment_status->label()))->toBeTrue();
    });

    it('uses email layout component', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);
        $content = (string) $mail->render();

        // Layout includes footer with store info
        expect(str_contains($content, 'Todos os direitos reservados'))->toBeTrue();
    });

    it('includes preheader text with order number', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail = $notification->toMail($this->user);
        $content = (string) $mail->render();

        // Preheader is hidden but present in HTML
        $preheader = "Seu pedido #{$this->order->order_number} foi recebido com sucesso";
        expect(str_contains($content, $preheader))->toBeTrue();
    });

    it('includes order number in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        expect($mail->subject)->toContain($this->order->order_number);
    });

    it('includes order items in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        expect(str_contains((string) $mail->render(), 'Test Product'))->toBeTrue();
    });

    it('includes shipping address in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);
        $content      = (string) $mail->render();

        expect(str_contains($content, 'Av Paulista'))->toBeTrue();
        expect(str_contains($content, 'Sao Paulo'))->toBeTrue();
    });

    it('includes order total in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        // R$ 125,00 = 12500 cents
        expect(str_contains((string) $mail->render(), '125,00'))->toBeTrue();
    });

    it('includes payment status in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        expect(str_contains((string) $mail->render(), 'Aprovado'))->toBeTrue();
    });

    it('includes Pix instructions for pending Pix payments', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'   => PaymentMethod::Pix,
            'status'   => PaymentStatus::Pending,
            'pix_code' => '00020126580014BR.GOV.BCB.PIX',
        ]);

        $this->order->refresh();

        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        expect(str_contains((string) $mail->render(), 'Pix'))->toBeTrue();
    });

    it('includes Bank Slip instructions for pending Bank Slip payments', function () {
        $this->order->update(['payment_status' => PaymentStatus::Pending]);

        Payment::factory()->for($this->order)->create([
            'method'            => PaymentMethod::BankSlip,
            'status'            => PaymentStatus::Pending,
            'bank_slip_barcode' => '23793.12345 12345.678901 12345.678901 1 12340000012500',
            'bank_slip_url'     => 'https://example.com/boleto.pdf',
        ]);

        $this->order->refresh();

        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        expect(str_contains((string) $mail->render(), 'Boleto'))->toBeTrue();
    });

    it('includes order tracking link in email', function () {
        $notification = new OrderConfirmationNotification($this->order);
        $mail         = $notification->toMail($this->user);

        // For authenticated users, uses customer.orders.show route
        expect(str_contains((string) $mail->render(), route('customer.orders.show', ['order' => $this->order->id])))->toBeTrue();
    });
});

describe('Order Created Event', function () {
    it('dispatches OrderCreated event automatically when order is created', function () {
        Event::fake([OrderCreated::class]);

        $order = Order::factory()->for($this->user)->create();

        Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    });

    it('listener sends email on OrderCreated event for authenticated user', function () {
        $listener = new SendOrderConfirmationEmail();
        $event    = new OrderCreated($this->order);

        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            OrderConfirmationNotification::class,
        );
    });

    it('listener sends email on OrderCreated event for guest user', function () {
        $guestOrder = Order::factory()->guest()->create([
            'guest_email' => 'guest@example.com',
            'guest_name'  => 'Guest User',
        ]);

        $listener = new SendOrderConfirmationEmail();
        $event    = new OrderCreated($guestOrder);

        $listener->handle($event);

        Notification::assertSentOnDemand(
            OrderConfirmationNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'guest@example.com',
        );
    });

    it('listener does not send email when guest has no email', function () {
        $guestOrder = Order::factory()->create([
            'user_id'     => null,
            'guest_email' => null,
            'guest_name'  => 'Guest User',
        ]);

        $listener = new SendOrderConfirmationEmail();
        $event    = new OrderCreated($guestOrder);

        $listener->handle($event);

        Notification::assertNothingSent();
    });
});
