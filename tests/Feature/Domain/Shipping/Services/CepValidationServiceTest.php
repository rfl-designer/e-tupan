<?php

declare(strict_types = 1);

use App\Domain\Shipping\Services\CepValidationService;
use Illuminate\Support\Facades\{Cache, Http};

describe('CepValidationService', function (): void {
    beforeEach(function (): void {
        $this->service = new CepValidationService();
        Cache::flush();
    });

    describe('format validation', function (): void {
        it('validates correct CEP format with mask', function (): void {
            expect($this->service->isValidFormat('01310-100'))->toBeTrue();
        });

        it('validates correct CEP format without mask', function (): void {
            expect($this->service->isValidFormat('01310100'))->toBeTrue();
        });

        it('rejects CEP with wrong length', function (): void {
            expect($this->service->isValidFormat('0131010'))->toBeFalse()
                ->and($this->service->isValidFormat('013101001'))->toBeFalse();
        });

        it('rejects CEP with letters', function (): void {
            expect($this->service->isValidFormat('0131a100'))->toBeFalse();
        });

        it('rejects empty CEP', function (): void {
            expect($this->service->isValidFormat(''))->toBeFalse();
        });
    });

    describe('sanitize', function (): void {
        it('removes mask from CEP', function (): void {
            expect($this->service->sanitize('01310-100'))->toBe('01310100');
        });

        it('removes spaces from CEP', function (): void {
            expect($this->service->sanitize('01310 100'))->toBe('01310100');
        });

        it('keeps only digits', function (): void {
            expect($this->service->sanitize('01.310-100'))->toBe('01310100');
        });
    });

    describe('format', function (): void {
        it('formats CEP with mask', function (): void {
            expect($this->service->format('01310100'))->toBe('01310-100');
        });

        it('formats already masked CEP', function (): void {
            expect($this->service->format('01310-100'))->toBe('01310-100');
        });
    });

    describe('lookup', function (): void {
        it('returns address data for valid CEP', function (): void {
            Http::fake([
                'viacep.com.br/ws/01310100/json/' => Http::response([
                    'cep'         => '01310-100',
                    'logradouro'  => 'Avenida Paulista',
                    'complemento' => 'de 1047 a 1865 - lado impar',
                    'bairro'      => 'Bela Vista',
                    'localidade'  => 'Sao Paulo',
                    'uf'          => 'SP',
                    'ibge'        => '3550308',
                    'gia'         => '1004',
                    'ddd'         => '11',
                    'siafi'       => '7107',
                ], 200),
            ]);

            $result = $this->service->lookup('01310100');

            expect($result)->not->toBeNull()
                ->and($result['zipcode'])->toBe('01310100')
                ->and($result['street'])->toBe('Avenida Paulista')
                ->and($result['neighborhood'])->toBe('Bela Vista')
                ->and($result['city'])->toBe('Sao Paulo')
                ->and($result['state'])->toBe('SP');
        });

        it('returns null for invalid CEP', function (): void {
            Http::fake([
                'viacep.com.br/ws/00000000/json/' => Http::response([
                    'erro' => true,
                ], 200),
            ]);

            $result = $this->service->lookup('00000000');

            expect($result)->toBeNull();
        });

        it('caches lookup results', function (): void {
            Http::fake([
                'viacep.com.br/ws/01310100/json/' => Http::response([
                    'cep'        => '01310-100',
                    'logradouro' => 'Avenida Paulista',
                    'bairro'     => 'Bela Vista',
                    'localidade' => 'Sao Paulo',
                    'uf'         => 'SP',
                ], 200),
            ]);

            $this->service->lookup('01310100');
            $this->service->lookup('01310100');

            Http::assertSentCount(1);
        });

        it('handles API errors gracefully', function (): void {
            Http::fake([
                'viacep.com.br/*' => Http::response(null, 500),
            ]);

            $result = $this->service->lookup('01310100');

            expect($result)->toBeNull();
        });

        it('handles connection errors gracefully', function (): void {
            Http::fake([
                'viacep.com.br/*' => Http::failedConnection(),
            ]);

            $result = $this->service->lookup('01310100');

            expect($result)->toBeNull();
        });
    });

    describe('validate', function (): void {
        it('returns true for valid and existing CEP', function (): void {
            Http::fake([
                'viacep.com.br/ws/01310100/json/' => Http::response([
                    'cep'        => '01310-100',
                    'logradouro' => 'Avenida Paulista',
                    'bairro'     => 'Bela Vista',
                    'localidade' => 'Sao Paulo',
                    'uf'         => 'SP',
                ], 200),
            ]);

            expect($this->service->validate('01310100'))->toBeTrue();
        });

        it('returns false for invalid format', function (): void {
            expect($this->service->validate('123'))->toBeFalse();
        });

        it('returns false for non-existing CEP', function (): void {
            Http::fake([
                'viacep.com.br/ws/00000000/json/' => Http::response([
                    'erro' => true,
                ], 200),
            ]);

            expect($this->service->validate('00000000'))->toBeFalse();
        });
    });
});
