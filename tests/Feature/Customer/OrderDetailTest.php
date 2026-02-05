<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\OrderNote;
use App\Domain\Checkout\Models\{Order, OrderItem};
use App\Domain\Customer\Livewire\OrderDetail;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\{actingAs, get};

describe('US-01: Pagina de detalhes do pedido', function () {
    describe('A pagina /minha-conta/pedidos/{id} exibe os detalhes do pedido', function () {
        it('displays the order detail page for the order owner', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $response = actingAs($user)->get(route('customer.orders.show', $order));

            $response->assertOk();
            /** @phpstan-ignore method.notFound */
            $response->assertSeeLivewire(OrderDetail::class);
        });

        it('loads the correct order data', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-TEST99',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSet('order.id', $order->id)
                ->assertSet('order.order_number', 'ORD-TEST99');
        });
    });

    describe('Apenas o proprietario do pedido pode acessar a pagina', function () {
        it('allows the order owner to access the page', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $response = actingAs($user)->get(route('customer.orders.show', $order));

            $response->assertOk();
        });

        it('denies access to other users with 403', function () {
            $owner     = User::factory()->create();
            $otherUser = User::factory()->create();
            $order     = Order::factory()->create([
                'user_id' => $owner->id,
            ]);

            $response = actingAs($otherUser)->get(route('customer.orders.show', $order));

            $response->assertForbidden();
        });
    });

    describe('Visitantes sao redirecionados para login', function () {
        it('redirects guests to login page', function () {
            $order = Order::factory()->create();

            get(route('customer.orders.show', $order))
                ->assertRedirect(route('login'));
        });
    });

    describe('Clientes que tentam acessar pedido de outro usuario recebem erro 403', function () {
        it('returns 403 when accessing another users order', function () {
            $owner    = User::factory()->create();
            $intruder = User::factory()->create();
            $order    = Order::factory()->create([
                'user_id' => $owner->id,
            ]);

            $response = actingAs($intruder)->get(route('customer.orders.show', $order));

            $response->assertForbidden();
        });

        it('returns 403 for guest orders accessed by logged in users', function () {
            $user       = User::factory()->create();
            $guestOrder = Order::factory()->create([
                'user_id'     => null,
                'guest_email' => 'guest@example.com',
            ]);

            $response = actingAs($user)->get(route('customer.orders.show', $guestOrder));

            $response->assertForbidden();
        });
    });

    describe('Pedidos inexistentes retornam erro 404', function () {
        it('returns 404 for non-existent order', function () {
            $user = User::factory()->create();

            $response = actingAs($user)->get('/minha-conta/pedidos/non-existent-id');

            $response->assertNotFound();
        });

        it('returns 404 for deleted orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $orderId = $order->id;
            $order->delete();

            $response = actingAs($user)->get("/minha-conta/pedidos/{$orderId}");

            $response->assertNotFound();
        });
    });

    describe('A pagina exibe o numero do pedido no header', function () {
        it('displays the order number in the page', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-HEADER1',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('ORD-HEADER1');
        });

        it('displays the order number in the page title', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-TITLE1',
            ]);

            $response = actingAs($user)->get(route('customer.orders.show', $order));

            $response->assertOk();
            $response->assertSee('ORD-TITLE1');
        });
    });
});

describe('US-05: Informacoes de entrega do pedido', function () {
    describe('O endereco de entrega completo e exibido', function () {
        it('displays the full shipping address', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'               => $user->id,
                'shipping_street'       => 'Rua das Flores',
                'shipping_number'       => '123',
                'shipping_complement'   => 'Apto 45',
                'shipping_neighborhood' => 'Centro',
                'shipping_city'         => 'São Paulo',
                'shipping_state'        => 'SP',
                'shipping_zipcode'      => '01234-567',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Rua das Flores')
                ->assertSee('123')
                ->assertSee('Apto 45')
                ->assertSee('Centro')
                ->assertSee('São Paulo')
                ->assertSee('SP')
                ->assertSee('01234-567');
        });

        it('displays address without complement when not provided', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'               => $user->id,
                'shipping_street'       => 'Av. Brasil',
                'shipping_number'       => '456',
                'shipping_complement'   => null,
                'shipping_neighborhood' => 'Jardim América',
                'shipping_city'         => 'Rio de Janeiro',
                'shipping_state'        => 'RJ',
                'shipping_zipcode'      => '22041-080',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Av. Brasil')
                ->assertSee('456')
                ->assertSee('Jardim América')
                ->assertSee('Rio de Janeiro')
                ->assertSee('RJ')
                ->assertSee('22041-080');
        });
    });

    describe('O nome do destinatario e exibido', function () {
        it('displays the recipient name', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'                 => $user->id,
                'shipping_recipient_name' => 'João da Silva',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('João da Silva');
        });
    });

    describe('O metodo de envio escolhido e exibido', function () {
        it('displays PAC shipping method', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'          => $user->id,
                'shipping_method'  => 'pac',
                'shipping_carrier' => 'Correios',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('PAC')
                ->assertSee('Correios');
        });

        it('displays SEDEX shipping method', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'          => $user->id,
                'shipping_method'  => 'sedex',
                'shipping_carrier' => 'Correios',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('SEDEX')
                ->assertSee('Correios');
        });

        it('displays custom shipping method', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'          => $user->id,
                'shipping_method'  => 'expresso',
                'shipping_carrier' => 'Jadlog',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Expresso')
                ->assertSee('Jadlog');
        });
    });

    describe('O valor do frete e exibido', function () {
        it('displays shipping cost', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'shipping_cost' => 2590, // R$ 25,90
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 25,90');
        });

        it('displays free shipping', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->freeShipping()->create([
                'user_id' => $user->id,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Grátis');
        });
    });

    describe('O prazo estimado de entrega e exibido', function () {
        it('displays estimated delivery time in business days', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'shipping_days' => 5,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('5 dias úteis');
        });

        it('displays singular day when delivery is 1 day', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'       => $user->id,
                'shipping_days' => 1,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('1 dia útil');
        });

        it('displays estimated delivery date when order is shipped', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'       => $user->id,
                'shipping_days' => 5,
            ]);

            $estimatedDate = $order->estimated_delivery_date;

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee($estimatedDate->format('d/m/Y'));
        });
    });
});

describe('US-06: Codigo de rastreamento do pedido', function () {
    describe('Quando disponivel, o codigo de rastreamento e exibido em destaque', function () {
        it('displays tracking code when available', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR123456789BR',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('BR123456789BR');
        });

        it('displays tracking code in a highlighted section', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR987654321BR',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Rastreamento')
                ->assertSee('BR987654321BR');
        });
    });

    describe('O codigo pode ser copiado com um clique', function () {
        it('displays copy button for tracking code', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR111222333BR',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml('x-data')
                ->assertSeeHtml('clipboard');
        });
    });

    describe('Um link para rastrear o pedido (pagina publica) e exibido', function () {
        it('displays link to public tracking page', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR444555666BR',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml(route('tracking.show', 'BR444555666BR'));
        });

        it('displays track order button text', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR777888999BR',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Rastrear Pedido');
        });
    });

    describe('Se o codigo nao estiver disponivel, uma mensagem informativa e exibida', function () {
        it('displays informative message when tracking not available for pending order', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->pending()->create([
                'user_id'         => $user->id,
                'tracking_number' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Rastreamento');
        });

        it('displays informative message when tracking not available for processing order', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->processing()->create([
                'user_id'         => $user->id,
                'tracking_number' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Rastreamento');
        });

        it('displays awaiting tracking message for shipped order without tracking', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Rastreamento')
                ->assertSee('Código de rastreamento em breve');
        });
    });

    describe('Pedidos com status Enviado ou Entregue devem ter secao de rastreamento visivel', function () {
        it('displays tracking section for shipped orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->shipped()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR123SHIPPED',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Rastreamento')
                ->assertSee('BR123SHIPPED');
        });

        it('displays tracking section for completed orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->completed()->create([
                'user_id'         => $user->id,
                'tracking_number' => 'BR123COMPLETED',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Rastreamento')
                ->assertSee('BR123COMPLETED');
        });

        it('does not display tracking section for pending orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->pending()->create([
                'user_id'         => $user->id,
                'tracking_number' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Rastreamento');
        });

        it('does not display tracking section for cancelled orders', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->cancelled()->create([
                'user_id'         => $user->id,
                'tracking_number' => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Rastreamento');
        });
    });
});

describe('US-08: Navegacao entre lista e detalhes do pedido', function () {
    beforeEach(function () {
        $this->user  = User::factory()->create();
        $this->order = Order::factory()->create([
            'user_id'      => $this->user->id,
            'order_number' => 'ORD-NAV001',
        ]);
    });

    describe('Um botao Voltar leva para a lista de pedidos', function () {
        it('displays back button linking to order list with wire:navigate', function () {
            Livewire::actingAs($this->user)
                ->test(OrderDetail::class, ['order' => $this->order])
                ->assertSee('Voltar para pedidos')
                ->assertSeeHtml(route('customer.orders'))
                ->assertSeeHtml('wire:navigate');
        });
    });

    describe('Breadcrumb exibe Minha Conta > Pedidos > #NUMERO', function () {
        it('displays complete breadcrumb navigation with all links', function () {
            $component = Livewire::actingAs($this->user)
                ->test(OrderDetail::class, ['order' => $this->order]);

            $component
                ->assertSee('Minha Conta')
                ->assertSee('Pedidos')
                ->assertSee('ORD-NAV001')
                ->assertSeeHtml(route('customer.dashboard'))
                ->assertSeeHtml(route('customer.orders'));

            $html = $component->html();
            expect(substr_count($html, 'wire:navigate'))->toBeGreaterThanOrEqual(3);
        });
    });
});

describe('US-07: Notas visiveis do pedido', function () {
    describe('Notas marcadas como visiveis ao cliente sao exibidas', function () {
        it('displays customer visible notes', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);
            OrderNote::factory()->customerVisible()->create([
                'order_id' => $order->id,
                'note'     => 'Esta nota é visível ao cliente',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Observações')
                ->assertSee('Esta nota é visível ao cliente');
        });

        it('displays multiple customer visible notes', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);
            OrderNote::factory()->customerVisible()->create([
                'order_id' => $order->id,
                'note'     => 'Primeira nota visível',
            ]);
            OrderNote::factory()->customerVisible()->create([
                'order_id' => $order->id,
                'note'     => 'Segunda nota visível',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Primeira nota visível')
                ->assertSee('Segunda nota visível');
        });
    });

    describe('Cada nota exibe a data e o conteudo', function () {
        it('displays note date and content', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);
            $note = OrderNote::factory()->customerVisible()->create([
                'order_id'   => $order->id,
                'note'       => 'Conteúdo da nota de teste',
                'created_at' => now()->subDays(2),
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Conteúdo da nota de teste')
                ->assertSee($note->created_at->format('d/m/Y'));
        });
    });

    describe('Notas internas (nao visiveis) nao aparecem para o cliente', function () {
        it('does not display internal notes', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);
            OrderNote::factory()->internalOnly()->create([
                'order_id' => $order->id,
                'note'     => 'Esta nota é interna e secreta',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Esta nota é interna e secreta');
        });

        it('displays only customer visible notes when both types exist', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);
            OrderNote::factory()->customerVisible()->create([
                'order_id' => $order->id,
                'note'     => 'Nota pública para o cliente',
            ]);
            OrderNote::factory()->internalOnly()->create([
                'order_id' => $order->id,
                'note'     => 'Nota interna confidencial',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Nota pública para o cliente')
                ->assertDontSee('Nota interna confidencial');
        });
    });

    describe('Se nao houver notas, a secao nao e exibida', function () {
        it('does not display notes section when no notes exist', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Observações');
        });

        it('does not display notes section when only internal notes exist', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);
            OrderNote::factory()->internalOnly()->create([
                'order_id' => $order->id,
                'note'     => 'Apenas nota interna',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertDontSee('Observações');
        });
    });
});

describe('US-09: Experiencia responsiva na pagina de detalhes', function () {
    beforeEach(function () {
        $this->user  = User::factory()->create();
        $this->order = Order::factory()->create([
            'user_id'      => $this->user->id,
            'order_number' => 'ORD-RESP01',
        ]);
        $this->html = fn () => Livewire::actingAs($this->user)
            ->test(OrderDetail::class, ['order' => $this->order->fresh()])
            ->html();
    });

    it('uses responsive layout classes for container and header', function () {
        $html = ($this->html)();

        expect($html)
            ->toContain('px-4')
            ->toContain('sm:px-6')
            ->toContain('lg:px-8')
            ->toContain('max-w-4xl')
            ->toContain('flex-col')
            ->toContain('sm:flex-row');
    });

    it('orders sections correctly for mobile-first experience', function () {
        $html = ($this->html)();

        $statusPosition   = strpos($html, 'Status');
        $itemsPosition    = strpos($html, 'Itens do Pedido');
        $deliveryPosition = strpos($html, 'Informações de Entrega');

        expect($statusPosition)
            ->toBeLessThan($itemsPosition)
            ->and($itemsPosition)
            ->toBeLessThan($deliveryPosition);
    });

    it('uses touch-friendly back button with wire:navigate', function () {
        Livewire::actingAs($this->user)
            ->test(OrderDetail::class, ['order' => $this->order])
            ->assertSee('Voltar para pedidos')
            ->assertSeeHtml('wire:navigate');
    });

    describe('with order items', function () {
        beforeEach(function () {
            OrderItem::factory()->create(['order_id' => $this->order->id]);
        });

        it('uses responsive image sizes and touch-friendly elements', function () {
            $html = ($this->html)();

            expect($html)
                ->toContain('size-20')
                ->toContain('sm:size-24')
                ->toContain('p-4')
                ->toContain('sm:hidden')
                ->toContain('sm:block');
        });
    });
});
