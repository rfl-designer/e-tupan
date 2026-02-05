<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Providers;

use App\Domain\Shipping\Contracts\{LabelGeneratorInterface, ShippingProviderInterface};
use App\Domain\Shipping\DTOs\{LabelResult, ShipmentRequest, ShippingOption, ShippingQuoteRequest};
use Illuminate\Support\Facades\{Cache, Http, Log};

class MelhorEnvioProvider implements LabelGeneratorInterface, ShippingProviderInterface
{
    private const string SANDBOX_URL = 'https://sandbox.melhorenvio.com.br';

    private const string PRODUCTION_URL = 'https://melhorenvio.com.br';

    /**
     * Calculate shipping options for a given request.
     *
     * @return array<ShippingOption>
     */
    public function calculate(ShippingQuoteRequest $request): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $cacheKey = $this->getCacheKey($request);
        $ttl      = config('shipping.cache.quotes_ttl', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($request) {
            return $this->fetchQuotes($request);
        });
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'Melhor Envio';
    }

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->getToken());
    }

    /**
     * Get the API base URL based on environment.
     */
    public function getBaseUrl(): string
    {
        $sandbox = config('shipping.providers.melhor_envio.sandbox', true);

        return $sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
    }

    /**
     * Test the API connection.
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/api/v2/me');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Melhor Envio connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Add shipment to cart for checkout.
     */
    public function addToCart(ShipmentRequest $request): LabelResult
    {
        try {
            $payload  = $this->buildCartPayload($request);
            $response = $this->makeRequest('POST', '/api/v2/me/cart', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio add to cart failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return LabelResult::failure(
                    'Falha ao adicionar ao carrinho: ' . $this->extractErrorMessage($response->json()),
                    (string) $response->status(),
                );
            }

            $data   = $response->json();
            $cartId = $data['id'] ?? null;

            if (!$cartId) {
                return LabelResult::failure('ID do carrinho nao retornado');
            }

            Log::info('Shipment added to Melhor Envio cart', [
                'cart_id' => $cartId,
            ]);

            return new LabelResult(
                success: true,
                shipmentId: $cartId,
            );
        } catch (\Exception $e) {
            Log::error('Melhor Envio add to cart exception', [
                'error' => $e->getMessage(),
            ]);

            return LabelResult::failure($e->getMessage());
        }
    }

    /**
     * Checkout (purchase) the shipment.
     */
    public function checkout(string $cartId): LabelResult
    {
        try {
            $payload = [
                'orders' => [$cartId],
            ];

            $response = $this->makeRequest('POST', '/api/v2/me/shipment/checkout', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio checkout failed', [
                    'cart_id' => $cartId,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);

                return LabelResult::failure(
                    'Falha no checkout: ' . $this->extractErrorMessage($response->json()),
                    (string) $response->status(),
                );
            }

            $data       = $response->json();
            $purchase   = $data['purchase'] ?? ($data[0] ?? null);
            $shipmentId = $purchase['id'] ?? $purchase['protocol'] ?? null;

            if (!$shipmentId) {
                return LabelResult::failure('ID do envio nao retornado');
            }

            Log::info('Shipment checkout completed', [
                'cart_id'     => $cartId,
                'shipment_id' => $shipmentId,
            ]);

            return new LabelResult(
                success: true,
                shipmentId: (string) $shipmentId,
            );
        } catch (\Exception $e) {
            Log::error('Melhor Envio checkout exception', [
                'cart_id' => $cartId,
                'error'   => $e->getMessage(),
            ]);

            return LabelResult::failure($e->getMessage());
        }
    }

    /**
     * Generate label for a purchased shipment.
     */
    public function generateLabel(string $shipmentId): LabelResult
    {
        try {
            $payload = [
                'orders' => [$shipmentId],
            ];

            $response = $this->makeRequest('POST', '/api/v2/me/shipment/generate', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio generate label failed', [
                    'shipment_id' => $shipmentId,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);

                return LabelResult::failure(
                    'Falha ao gerar etiqueta: ' . $this->extractErrorMessage($response->json()),
                    (string) $response->status(),
                );
            }

            $data     = $response->json();
            $shipment = $data[0] ?? $data;

            $labelUrl       = $shipment['print']['url'] ?? null;
            $trackingNumber = $shipment['tracking'] ?? null;

            Log::info('Label generated successfully', [
                'shipment_id' => $shipmentId,
                'tracking'    => $trackingNumber,
            ]);

            return LabelResult::success(
                labelUrl: $labelUrl ?? '',
                trackingNumber: $trackingNumber ?? '',
                shipmentId: $shipmentId,
            );
        } catch (\Exception $e) {
            Log::error('Melhor Envio generate label exception', [
                'shipment_id' => $shipmentId,
                'error'       => $e->getMessage(),
            ]);

            return LabelResult::failure($e->getMessage());
        }
    }

    /**
     * Print label (get printable URL).
     */
    public function printLabel(string $shipmentId): LabelResult
    {
        try {
            $payload = [
                'orders' => [$shipmentId],
            ];

            $response = $this->makeRequest('POST', '/api/v2/me/shipment/print', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio print label failed', [
                    'shipment_id' => $shipmentId,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);

                return LabelResult::failure(
                    'Falha ao imprimir etiqueta: ' . $this->extractErrorMessage($response->json()),
                    (string) $response->status(),
                );
            }

            $data = $response->json();
            $url  = $data['url'] ?? null;

            if (!$url) {
                return LabelResult::failure('URL de impressao nao retornada');
            }

            return new LabelResult(
                success: true,
                labelUrl: $url,
                shipmentId: $shipmentId,
            );
        } catch (\Exception $e) {
            Log::error('Melhor Envio print label exception', [
                'shipment_id' => $shipmentId,
                'error'       => $e->getMessage(),
            ]);

            return LabelResult::failure($e->getMessage());
        }
    }

    /**
     * Cancel a shipment.
     */
    public function cancelShipment(string $shipmentId): bool
    {
        try {
            $payload = [
                'order' => [
                    'id'          => $shipmentId,
                    'reason_id'   => 2, // Reason: Customer requested cancellation
                    'description' => 'Cancelamento solicitado pelo sistema',
                ],
            ];

            $response = $this->makeRequest('POST', '/api/v2/me/shipment/cancel', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio cancel shipment failed', [
                    'shipment_id' => $shipmentId,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);

                return false;
            }

            Log::info('Shipment cancelled successfully', [
                'shipment_id' => $shipmentId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Melhor Envio cancel shipment exception', [
                'shipment_id' => $shipmentId,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get shipment tracking info.
     *
     * @return array<string, mixed>|null
     */
    public function getTracking(string $shipmentId): ?array
    {
        try {
            $payload = [
                'orders' => [$shipmentId],
            ];

            $response = $this->makeRequest('POST', '/api/v2/me/shipment/tracking', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio tracking request failed', [
                    'shipment_id' => $shipmentId,
                    'status'      => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            return $data[$shipmentId] ?? ($data[0] ?? null);
        } catch (\Exception $e) {
            Log::error('Melhor Envio tracking exception', [
                'shipment_id' => $shipmentId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch quotes from the Melhor Envio API.
     *
     * @return array<ShippingOption>
     */
    private function fetchQuotes(ShippingQuoteRequest $request): array
    {
        try {
            $payload  = $this->buildQuotePayload($request);
            $response = $this->makeRequest('POST', '/api/v2/me/shipment/calculate', $payload);

            if ($response->failed()) {
                Log::error('Melhor Envio quote request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [];
            }

            return $this->parseQuoteResponse($response->json());
        } catch (\Exception $e) {
            Log::error('Melhor Envio quote request exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Build the payload for quote request.
     *
     * @return array<string, mixed>
     */
    private function buildQuotePayload(ShippingQuoteRequest $request): array
    {
        $originZipcode      = preg_replace('/\D/', '', config('shipping.origin.zipcode', ''));
        $destinationZipcode = $request->cleanZipcode();

        return [
            'from' => [
                'postal_code' => $originZipcode,
            ],
            'to' => [
                'postal_code' => $destinationZipcode,
            ],
            'package' => [
                'weight' => $request->totalWeight / 1000, // Convert grams to kg
                'width'  => $request->totalWidth,
                'height' => $request->totalHeight,
                'length' => $request->totalLength,
            ],
            'options' => [
                'insurance_value' => $request->totalValue / 100, // Convert cents to reais
                'receipt'         => false,
                'own_hand'        => false,
            ],
        ];
    }

    /**
     * Build the payload for cart (shipment) request.
     *
     * @return array<string, mixed>
     */
    private function buildCartPayload(ShipmentRequest $request): array
    {
        $origin = config('shipping.origin', []);

        return [
            'service' => $request->serviceCode,
            'from'    => [
                'name'             => $origin['name'] ?? config('app.name'),
                'phone'            => $origin['phone'] ?? '',
                'email'            => $origin['email'] ?? config('mail.from.address'),
                'document'         => $origin['document'] ?? '',
                'company_document' => $origin['company_document'] ?? '',
                'state_register'   => $origin['state_register'] ?? '',
                'address'          => $origin['street'] ?? '',
                'complement'       => $origin['complement'] ?? '',
                'number'           => $origin['number'] ?? '',
                'district'         => $origin['neighborhood'] ?? '',
                'city'             => $origin['city'] ?? '',
                'country_id'       => 'BR',
                'postal_code'      => preg_replace('/\D/', '', $origin['zipcode'] ?? ''),
                'note'             => '',
            ],
            'to' => [
                'name'        => $request->recipient->name,
                'phone'       => $request->recipient->phone ?? '',
                'email'       => $request->recipient->email ?? '',
                'document'    => $request->recipient->document ?? '',
                'address'     => $request->address->street,
                'complement'  => $request->address->complement ?? '',
                'number'      => $request->address->number,
                'district'    => $request->address->neighborhood,
                'city'        => $request->address->city,
                'state_abbr'  => $request->address->stateAbbr,
                'country_id'  => 'BR',
                'postal_code' => preg_replace('/\D/', '', $request->address->postalCode),
                'note'        => '',
            ],
            'products' => $request->products,
            'volumes'  => [
                [
                    'weight' => $request->package->weight,
                    'width'  => $request->package->width,
                    'height' => $request->package->height,
                    'length' => $request->package->length,
                ],
            ],
            'options' => [
                'insurance_value' => $request->insuranceValue / 100,
                'receipt'         => $request->receipt,
                'own_hand'        => $request->ownHand,
                'non_commercial'  => true,
            ],
        ];
    }

    /**
     * Parse the quote response from API.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array<ShippingOption>
     */
    private function parseQuoteResponse(array $data): array
    {
        $options      = [];
        $handlingDays = (int) config('shipping.handling_days', 1);

        foreach ($data as $item) {
            if (!empty($item['error'])) {
                continue;
            }

            if (empty($item['price'])) {
                continue;
            }

            $deliveryRange   = $item['delivery_range'] ?? null;
            $deliveryDaysMin = $deliveryRange['min'] ?? $item['delivery_time'] ?? 0;
            $deliveryDaysMax = $deliveryRange['max'] ?? $item['delivery_time'] ?? 0;

            $options[] = new ShippingOption(
                code: (string) $item['id'],
                name: $item['name'],
                price: (int) round((float) $item['price'] * 100), // Convert to cents
                deliveryDaysMin: (int) $deliveryDaysMin + $handlingDays,
                deliveryDaysMax: (int) $deliveryDaysMax + $handlingDays,
                carrier: $item['company']['name'] ?? null,
            );
        }

        return $options;
    }

    /**
     * Make an HTTP request to the Melhor Envio API.
     *
     * @param  array<string, mixed>|null  $data
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null): \Illuminate\Http\Client\Response
    {
        $url = $this->getBaseUrl() . $endpoint;

        $pendingRequest = Http::withHeaders([
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken(),
            'User-Agent'    => 'RGNT E-commerce/1.0',
        ])->timeout(30);

        return match (strtoupper($method)) {
            'GET'    => $pendingRequest->get($url, $data ?? []),
            'POST'   => $pendingRequest->post($url, $data ?? []),
            'PUT'    => $pendingRequest->put($url, $data ?? []),
            'DELETE' => $pendingRequest->delete($url, $data ?? []),
            default  => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Get the API token.
     */
    private function getToken(): ?string
    {
        return config('shipping.providers.melhor_envio.token');
    }

    /**
     * Generate a cache key for the quote request.
     */
    private function getCacheKey(ShippingQuoteRequest $request): string
    {
        $hash = md5(implode('|', [
            $request->cleanZipcode(),
            $request->totalWeight,
            $request->totalLength,
            $request->totalWidth,
            $request->totalHeight,
            $request->totalValue,
        ]));

        return "shipping:quote:{$hash}";
    }

    /**
     * Extract error message from API response.
     *
     * @param  array<string, mixed>|null  $data
     */
    private function extractErrorMessage(?array $data): string
    {
        if (!$data) {
            return 'Erro desconhecido';
        }

        if (isset($data['message'])) {
            return $data['message'];
        }

        if (isset($data['error'])) {
            return is_array($data['error']) ? json_encode($data['error']) : $data['error'];
        }

        if (isset($data['errors'])) {
            return is_array($data['errors']) ? implode(', ', $data['errors']) : $data['errors'];
        }

        return 'Erro na requisicao';
    }
}
