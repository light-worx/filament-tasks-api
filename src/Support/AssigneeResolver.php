<?php

namespace Lightworx\FilamentTasks\Support;

use Illuminate\Support\Facades\Cache;

class AssigneeResolver
{
    public static function isConfigured(): bool
    {
        return filled(config('filament-tasks.assignee_model'));
    }

    public static function options(): array
    {
        if (! static::isConfigured()) {
            return [];
        }

        return Cache::remember('filament_tasks:assignee_options', 300, function () {
            $model      = config('filament-tasks.assignee_model');
            $labelField = config('filament-tasks.assignee_label_field', 'name');
            $emailField = config('filament-tasks.assignee_email_field', 'email');
            $orderBy    = config('filament-tasks.assignee_order_by') ?? null;

            $query = $model::query();

            $instance      = new $model();
            $realColumns   = $instance->getFillable();
            $orderByField  = $orderBy ?? $labelField;
            $orderInSql    = in_array($orderByField, $realColumns);

            if ($orderInSql) {
                $query->orderBy($orderByField);
            }

            // Fetch all columns so accessors work correctly
            $records = $query->get();

            // Sort in PHP if the order field is an accessor
            if (! $orderInSql) {
                $records = $records->sortBy(fn ($r) => $r->{$orderByField});
            }

            return $records
                ->mapWithKeys(fn ($record) => [
                    $record->{$emailField} => $record->{$labelField},
                ])
                ->toArray();
        });
    }

    /**
     * Returns the display label for a given email address.
     */
    public static function labelForEmail(?string $email): string
    {
        if (! $email) return '—';
        if (! static::isConfigured()) return $email;

        return static::options()[$email] ?? $email;
    }

    /**
     * Flush the assignee options cache.
     */
    public static function flush(): void
    {
        Cache::forget('filament_tasks:assignee_options');
    }
}
