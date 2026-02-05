<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use Illuminate\Support\Facades\{Cache, Http, Log};

class CepValidationService
{
    private const string VIACEP_URL = 'https://viacep.com.br/ws';

    /**
     * Validate if CEP has correct format (8 digits).
     */
    public function isValidFormat(string $cep): bool
    {
        $sanitized = $this->sanitize($cep);

        return preg_match('/^\d{8}$/', $sanitized) === 1;
    }

    /**
     * Sanitize CEP removing non-numeric characters.
     */
    public function sanitize(string $cep): string
    {
        return preg_replace('/\D/', '', $cep) ?? '';
    }

    /**
     * Format CEP with mask (XXXXX-XXX).
     */
    public function format(string $cep): string
    {
        $sanitized = $this->sanitize($cep);

        if (strlen($sanitized) !== 8) {
            return $cep;
        }

        return substr($sanitized, 0, 5) . '-' . substr($sanitized, 5);
    }

    /**
     * Lookup CEP in ViaCEP API.
     *
     * @return array{zipcode: string, street: string|null, complement: string|null, neighborhood: string|null, city: string, state: string}|null
     */
    public function lookup(string $cep): ?array
    {
        $sanitized = $this->sanitize($cep);

        if (!$this->isValidFormat($sanitized)) {
            return null;
        }

        $cacheKey = "cep:{$sanitized}";
        $cacheTtl = config('shipping.cache.cep_ttl', 86400);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($sanitized) {
            return $this->fetchFromApi($sanitized);
        });
    }

    /**
     * Validate if CEP exists (format and API lookup).
     */
    public function validate(string $cep): bool
    {
        if (!$this->isValidFormat($cep)) {
            return false;
        }

        return $this->lookup($cep) !== null;
    }

    /**
     * Get validation error message.
     */
    public function getErrorMessage(string $cep): ?string
    {
        if (empty($cep)) {
            return 'CEP e obrigatorio.';
        }

        if (!$this->isValidFormat($cep)) {
            return 'CEP deve conter 8 digitos.';
        }

        if ($this->lookup($cep) === null) {
            return 'CEP nao encontrado.';
        }

        return null;
    }

    /**
     * Fetch CEP data from ViaCEP API.
     *
     * @return array{zipcode: string, street: string|null, complement: string|null, neighborhood: string|null, city: string, state: string}|null
     */
    private function fetchFromApi(string $cep): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get(self::VIACEP_URL . "/{$cep}/json/");

            if ($response->failed()) {
                Log::warning('ViaCEP API request failed', [
                    'cep'    => $cep,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            if (isset($data['erro']) && $data['erro'] === true) {
                return null;
            }

            return [
                'zipcode'      => $this->sanitize($data['cep'] ?? $cep),
                'street'       => $data['logradouro'] ?? null,
                'complement'   => $data['complemento'] ?? null,
                'neighborhood' => $data['bairro'] ?? null,
                'city'         => $data['localidade'] ?? '',
                'state'        => $data['uf'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('ViaCEP API error', [
                'cep'   => $cep,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
