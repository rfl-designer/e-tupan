<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Enums\ActivityAction;
use App\Domain\Admin\Models\{ActivityLog, Admin};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth, Request};

class ActivityLogService
{
    public function log(
        ActivityAction $action,
        Model $subject,
        ?string $description = null,
        ?array $properties = null,
        ?Admin $admin = null,
    ): ActivityLog {
        $admin = $admin ?? Auth::guard('admin')->user();

        $description = $description ?? $this->generateDescription($action, $subject);

        return ActivityLog::query()->create([
            'admin_id'     => $admin?->id,
            'admin_name'   => $admin?->name,
            'action'       => $action,
            'subject_type' => $subject::class,
            'subject_id'   => (string) $subject->getKey(),
            'description'  => $description,
            'properties'   => $properties,
            'ip_address'   => Request::ip(),
            'user_agent'   => Request::userAgent(),
        ]);
    }

    public function logCreated(Model $subject, ?string $description = null, ?array $properties = null): ActivityLog
    {
        return $this->log(ActivityAction::Created, $subject, $description, $properties);
    }

    public function logUpdated(Model $subject, ?string $description = null, ?array $properties = null): ActivityLog
    {
        $changes  = $subject->getChanges();
        $original = collect($subject->getOriginal())->only(array_keys($changes))->toArray();

        $properties = $properties ?? [
            'old' => $this->sanitizeProperties($original),
            'new' => $this->sanitizeProperties($changes),
        ];

        return $this->log(ActivityAction::Updated, $subject, $description, $properties);
    }

    public function logDeleted(Model $subject, ?string $description = null, ?array $properties = null): ActivityLog
    {
        return $this->log(ActivityAction::Deleted, $subject, $description, $properties);
    }

    public function logRestored(Model $subject, ?string $description = null): ActivityLog
    {
        return $this->log(ActivityAction::Restored, $subject, $description);
    }

    public function logStatusChanged(Model $subject, string $oldStatus, string $newStatus): ActivityLog
    {
        return $this->log(
            ActivityAction::StatusChanged,
            $subject,
            null,
            ['old_status' => $oldStatus, 'new_status' => $newStatus],
        );
    }

    public function logLogin(Admin $admin): ActivityLog
    {
        return $this->log(ActivityAction::LoggedIn, $admin, 'Fez login no painel', null, $admin);
    }

    public function logLogout(Admin $admin): ActivityLog
    {
        return $this->log(ActivityAction::LoggedOut, $admin, 'Fez logout do painel', null, $admin);
    }

    public function logExport(Model $subject, string $type): ActivityLog
    {
        return $this->log(ActivityAction::Exported, $subject, "Exportou dados em {$type}");
    }

    public function logRefund(Model $subject, int $amount): ActivityLog
    {
        return $this->log(
            ActivityAction::Refunded,
            $subject,
            'Realizou reembolso de R$ ' . number_format($amount / 100, 2, ',', '.'),
            ['amount' => $amount],
        );
    }

    private function generateDescription(ActivityAction $action, Model $subject): string
    {
        $modelName  = class_basename($subject);
        $modelLabel = $this->getModelLabel($modelName);
        $identifier = $this->getModelIdentifier($subject);

        return "{$action->label()} {$modelLabel} {$identifier}";
    }

    private function getModelLabel(string $modelName): string
    {
        return match ($modelName) {
            'Product'   => 'produto',
            'Category'  => 'categoria',
            'Order'     => 'pedido',
            'Admin'     => 'administrador',
            'User'      => 'cliente',
            'Coupon'    => 'cupom',
            'Shipment'  => 'envio',
            'Attribute' => 'atributo',
            default     => strtolower($modelName),
        };
    }

    private function getModelIdentifier(Model $subject): string
    {
        if (method_exists($subject, 'getActivityIdentifier')) {
            return $subject->getActivityIdentifier();
        }

        foreach (['name', 'title', 'order_number', 'code', 'email'] as $field) {
            if (isset($subject->{$field})) {
                return "\"{$subject->{$field}}\"";
            }
        }

        return "#{$subject->getKey()}";
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function sanitizeProperties(array $properties): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'api_key', 'two_factor'];

        return collect($properties)
            ->filter(fn ($value, $key) => !collect($sensitiveFields)->contains(
                fn ($field) => str_contains(strtolower($key), $field),
            ))
            ->toArray();
    }
}
