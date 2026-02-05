<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Services;

use App\Domain\Checkout\Models\{Order, Payment, PaymentLog};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Request;

class PaymentLogService
{
    /**
     * Sensitive fields that should never be logged.
     *
     * @var list<string>
     */
    private const SENSITIVE_FIELDS = [
        'card_number',
        'cvv',
        'cvc',
        'security_code',
        'token',
        'access_token',
        'secret',
        'password',
        'pin',
    ];

    /**
     * Log a payment action.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(
        string $action,
        string $status,
        array $data = [],
    ): PaymentLog {
        $sanitizedRequest  = $this->sanitize($data['request'] ?? []);
        $sanitizedResponse = $this->sanitize($data['response'] ?? []);

        return PaymentLog::query()->create([
            'payment_id'       => $data['payment_id'] ?? null,
            'order_id'         => $data['order_id'] ?? null,
            'gateway'          => $data['gateway'] ?? config('payment.default', 'unknown'),
            'action'           => $action,
            'status'           => $status,
            'transaction_id'   => $data['transaction_id'] ?? null,
            'request_data'     => $sanitizedRequest ?: null,
            'response_data'    => $sanitizedResponse ?: null,
            'error_message'    => $data['error_message'] ?? null,
            'ip_address'       => $data['ip_address'] ?? Request::ip(),
            'user_agent'       => $data['user_agent'] ?? Request::userAgent(),
            'response_time_ms' => $data['response_time_ms'] ?? null,
        ]);
    }

    /**
     * Log a successful action.
     *
     * @param  array<string, mixed>  $data
     */
    public function logSuccess(string $action, array $data = []): PaymentLog
    {
        return $this->log($action, 'success', $data);
    }

    /**
     * Log a failed action.
     *
     * @param  array<string, mixed>  $data
     */
    public function logFailure(string $action, array $data = []): PaymentLog
    {
        return $this->log($action, 'failed', $data);
    }

    /**
     * Log an error.
     *
     * @param  array<string, mixed>  $data
     */
    public function logError(string $action, array $data = []): PaymentLog
    {
        return $this->log($action, 'error', $data);
    }

    /**
     * Get logs for a specific payment.
     *
     * @return Collection<int, PaymentLog>
     */
    public function getByPayment(string $paymentId): Collection
    {
        return PaymentLog::query()
            ->forPayment($paymentId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get logs for a specific order.
     *
     * @return Collection<int, PaymentLog>
     */
    public function getByOrder(string $orderId): Collection
    {
        return PaymentLog::query()
            ->forOrder($orderId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get logs with filters and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<PaymentLog>
     */
    public function getFiltered(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = PaymentLog::query()->orderByDesc('created_at');

        if (!empty($filters['order_id'])) {
            $query->forOrder($filters['order_id']);
        }

        if (!empty($filters['payment_id'])) {
            $query->forPayment($filters['payment_id']);
        }

        if (!empty($filters['gateway'])) {
            $query->forGateway($filters['gateway']);
        }

        if (!empty($filters['action'])) {
            $query->forAction($filters['action']);
        }

        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Cleanup old logs.
     */
    public function cleanup(?int $days = null): int
    {
        $days ??= (int) config('payment.logging.retention_days', 90);

        return PaymentLog::query()
            ->olderThan($days)
            ->delete();
    }

    /**
     * Get log statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(?int $days = 30): array
    {
        $query = PaymentLog::query()->fromLastDays($days);

        $total      = $query->count();
        $successful = (clone $query)->successful()->count();
        $failed     = (clone $query)->failed()->count();

        $byGateway = PaymentLog::query()
            ->fromLastDays($days)
            ->selectRaw('gateway, count(*) as count')
            ->groupBy('gateway')
            ->pluck('count', 'gateway')
            ->toArray();

        $byAction = PaymentLog::query()
            ->fromLastDays($days)
            ->selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $avgResponseTime = PaymentLog::query()
            ->fromLastDays($days)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        return [
            'total'                => $total,
            'successful'           => $successful,
            'failed'               => $failed,
            'success_rate'         => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'by_gateway'           => $byGateway,
            'by_action'            => $byAction,
            'avg_response_time_ms' => $avgResponseTime ? round($avgResponseTime, 2) : null,
        ];
    }

    /**
     * Sanitize data to remove sensitive information.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);

                continue;
            }

            $lowerKey = strtolower((string) $key);

            foreach (self::SENSITIVE_FIELDS as $sensitiveField) {
                if (str_contains($lowerKey, $sensitiveField)) {
                    $sanitized[$key] = '[REDACTED]';

                    continue 2;
                }
            }

            // Mask card numbers (keep only last 4 digits)
            if ($this->isCardNumber($key, $value)) {
                $sanitized[$key] = $this->maskCardNumber($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * Check if a value looks like a card number.
     */
    private function isCardNumber(string $key, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $lowerKey    = strtolower($key);
        $isCardField = str_contains($lowerKey, 'card') || str_contains($lowerKey, 'numero');

        $digitsOnly = preg_replace('/\D/', '', $value);

        if (!$digitsOnly) {
            return false;
        }

        $length = strlen($digitsOnly);

        return $isCardField && $length >= 13 && $length <= 19;
    }

    /**
     * Mask a card number to show only last 4 digits.
     */
    private function maskCardNumber(string $cardNumber): string
    {
        $digitsOnly = preg_replace('/\D/', '', $cardNumber);

        if (!$digitsOnly || strlen($digitsOnly) < 4) {
            return '[REDACTED]';
        }

        $lastFour = substr($digitsOnly, -4);

        return '**** **** **** ' . $lastFour;
    }
}
