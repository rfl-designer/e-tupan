<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Marketing\Enums\CouponType;
use App\Domain\Marketing\Models\Coupon;

describe('Coupon Admin CRUD', function () {
    beforeEach(function () {
        $this->admin = Admin::factory()->withTwoFactor()->create();
    });

    describe('index', function () {
        it('lists all coupons', function () {
            Coupon::factory()->count(3)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.index'));

            $response->assertOk()
                ->assertSee('Cupons de Desconto')
                ->assertViewHas('coupons');
        });

        it('filters by status active', function () {
            Coupon::factory()->active()->count(2)->create();
            Coupon::factory()->inactive()->count(3)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.index', ['status' => 'active']));

            $response->assertOk();
            expect($response->viewData('coupons'))->toHaveCount(2);
        });

        it('filters by status inactive', function () {
            Coupon::factory()->active()->count(2)->create();
            Coupon::factory()->inactive()->count(3)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.index', ['status' => 'inactive']));

            $response->assertOk();
            expect($response->viewData('coupons'))->toHaveCount(3);
        });

        it('filters by type', function () {
            Coupon::factory()->percentage()->count(2)->create();
            Coupon::factory()->fixed()->count(3)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.index', ['type' => 'percentage']));

            $response->assertOk();
            expect($response->viewData('coupons'))->toHaveCount(2);
        });

        it('searches by code', function () {
            Coupon::factory()->withCode('PROMO10')->create();
            Coupon::factory()->withCode('SALE20')->create();
            Coupon::factory()->withCode('DISCOUNT30')->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.index', ['search' => 'PROMO']));

            $response->assertOk();
            expect($response->viewData('coupons'))->toHaveCount(1);
        });

        it('searches by name', function () {
            Coupon::factory()->create(['name' => 'Promocao de Verao']);
            Coupon::factory()->create(['name' => 'Black Friday']);
            Coupon::factory()->create(['name' => 'Natal']);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.index', ['search' => 'Black']));

            $response->assertOk();
            expect($response->viewData('coupons'))->toHaveCount(1);
        });

        it('requires admin authentication', function () {
            $response = $this->get(route('admin.coupons.index'));

            $response->assertRedirect(route('admin.login'));
        });
    });

    describe('create', function () {
        it('shows create form', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.create'));

            $response->assertOk()
                ->assertSee('Novo Cupom')
                ->assertViewHas('types');
        });
    });

    describe('store', function () {
        it('creates percentage coupon', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'      => 'PERCENT10',
                    'name'      => 'Desconto de 10%',
                    'type'      => 'percentage',
                    'value'     => 10,
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('admin.coupons.index'))
                ->assertSessionHas('success');

            $coupon = Coupon::where('code', 'PERCENT10')->first();
            expect($coupon)
                ->not->toBeNull()
                ->type->toBe(CouponType::Percentage)
                ->value->toBe(10);
        });

        it('creates fixed coupon with value in cents', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'      => 'FIXED25',
                    'name'      => 'Desconto de R$ 25',
                    'type'      => 'fixed',
                    'value'     => 25.50, // R$ 25,50
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('admin.coupons.index'));

            $coupon = Coupon::where('code', 'FIXED25')->first();
            expect($coupon)
                ->not->toBeNull()
                ->type->toBe(CouponType::Fixed)
                ->value->toBe(2550); // In cents
        });

        it('creates free shipping coupon', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'      => 'FREESHIP',
                    'name'      => 'Frete Gratis',
                    'type'      => 'free_shipping',
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('admin.coupons.index'));

            $coupon = Coupon::where('code', 'FREESHIP')->first();
            expect($coupon)
                ->not->toBeNull()
                ->type->toBe(CouponType::FreeShipping);
        });

        it('creates coupon with all options', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'                 => 'FULLOPT',
                    'name'                 => 'Cupom Completo',
                    'description'          => 'Cupom com todas as opcoes',
                    'type'                 => 'percentage',
                    'value'                => 20,
                    'minimum_order_value'  => 100, // R$ 100
                    'maximum_discount'     => 50, // R$ 50 max
                    'usage_limit'          => 100,
                    'usage_limit_per_user' => 2,
                    'starts_at'            => '2026-01-01T00:00',
                    'expires_at'           => '2026-12-31T23:59',
                    'is_active'            => true,
                ]);

            $response->assertRedirect(route('admin.coupons.index'));

            $coupon = Coupon::where('code', 'FULLOPT')->first();
            expect($coupon)
                ->minimum_order_value->toBe(10000) // R$ 100 in cents
                ->maximum_discount->toBe(5000) // R$ 50 in cents
                ->usage_limit->toBe(100)
                ->usage_limit_per_user->toBe(2);
        });

        it('validates required fields', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), []);

            $response->assertSessionHasErrors(['code', 'name', 'type']);
        });

        it('validates unique code', function () {
            Coupon::factory()->withCode('EXISTING')->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'  => 'EXISTING',
                    'name'  => 'Novo Cupom',
                    'type'  => 'percentage',
                    'value' => 10,
                ]);

            $response->assertSessionHasErrors(['code']);
        });

        it('converts code to uppercase', function () {
            actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'  => 'lowercase',
                    'name'  => 'Test',
                    'type'  => 'percentage',
                    'value' => 10,
                ]);

            $coupon = Coupon::where('code', 'LOWERCASE')->first();
            expect($coupon)->not->toBeNull();
        });

        it('validates expires_at after starts_at', function () {
            $response = actingAsAdminWith2FA($this, $this->admin)
                ->post(route('admin.coupons.store'), [
                    'code'       => 'INVALID',
                    'name'       => 'Test',
                    'type'       => 'percentage',
                    'value'      => 10,
                    'starts_at'  => '2026-12-31T00:00',
                    'expires_at' => '2026-01-01T00:00',
                ]);

            $response->assertSessionHasErrors(['expires_at']);
        });
    });

    describe('edit', function () {
        it('shows edit form with coupon data', function () {
            $coupon = Coupon::factory()->percentage(15)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.edit', $coupon));

            $response->assertOk()
                ->assertSee('Editar Cupom')
                ->assertSee($coupon->code)
                ->assertViewHas('coupon');
        });

        it('shows usage statistics', function () {
            $coupon = Coupon::factory()->create(['times_used' => 42, 'usage_limit' => 100]);

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->get(route('admin.coupons.edit', $coupon));

            $response->assertOk()
                ->assertSee('42')
                ->assertSee('100');
        });
    });

    describe('update', function () {
        it('updates coupon', function () {
            $coupon = Coupon::factory()->percentage(10)->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->put(route('admin.coupons.update', $coupon), [
                    'code'      => 'UPDATED',
                    'name'      => 'Updated Name',
                    'type'      => 'percentage',
                    'value'     => 20,
                    'is_active' => true,
                ]);

            $response->assertRedirect(route('admin.coupons.index'))
                ->assertSessionHas('success');

            $coupon->refresh();
            expect($coupon)
                ->code->toBe('UPDATED')
                ->name->toBe('Updated Name')
                ->value->toBe(20);
        });

        it('allows same code on update', function () {
            $coupon = Coupon::factory()->withCode('SAME')->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->put(route('admin.coupons.update', $coupon), [
                    'code'  => 'SAME',
                    'name'  => 'Updated Name',
                    'type'  => 'percentage',
                    'value' => 10,
                ]);

            $response->assertRedirect(route('admin.coupons.index'));
        });

        it('validates unique code against other coupons', function () {
            Coupon::factory()->withCode('OTHER')->create();
            $coupon = Coupon::factory()->withCode('MINE')->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->put(route('admin.coupons.update', $coupon), [
                    'code'  => 'OTHER',
                    'name'  => 'Test',
                    'type'  => 'percentage',
                    'value' => 10,
                ]);

            $response->assertSessionHasErrors(['code']);
        });
    });

    describe('destroy', function () {
        it('deletes coupon', function () {
            $coupon = Coupon::factory()->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->delete(route('admin.coupons.destroy', $coupon));

            $response->assertRedirect(route('admin.coupons.index'))
                ->assertSessionHas('success');

            expect(Coupon::find($coupon->id))->toBeNull();
        });

        it('soft deletes coupon', function () {
            $coupon = Coupon::factory()->create();

            actingAsAdminWith2FA($this, $this->admin)
                ->delete(route('admin.coupons.destroy', $coupon));

            expect(Coupon::withTrashed()->find($coupon->id))->not->toBeNull();
        });
    });

    describe('toggleActive', function () {
        it('activates inactive coupon', function () {
            $coupon = Coupon::factory()->inactive()->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->patch(route('admin.coupons.toggle-active', $coupon));

            $response->assertRedirect()
                ->assertSessionHas('success', 'Cupom ativado com sucesso!');

            expect($coupon->fresh()->is_active)->toBeTrue();
        });

        it('deactivates active coupon', function () {
            $coupon = Coupon::factory()->active()->create();

            $response = actingAsAdminWith2FA($this, $this->admin)
                ->patch(route('admin.coupons.toggle-active', $coupon));

            $response->assertRedirect()
                ->assertSessionHas('success', 'Cupom desativado com sucesso!');

            expect($coupon->fresh()->is_active)->toBeFalse();
        });
    });
});
