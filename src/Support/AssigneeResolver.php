<?php

namespace Lightworx\FilamentTasks\Support;

use Illuminate\Support\Facades\Cache;

class AssigneeResolver
{
    /**
     * Whether the assignee model is configured.
     */
    public static function isConfigured(): bool
    {
        return filled(config('filament-tasks.assignee_model'));
    }

    /**
     * Returns options for a Select input keyed by email, labelled by name.
     * ['john@example.com' => 'John Smith', ...]
     *
     * Cached for 5 minutes since the local model list rarely changes.
     */
    public static function options(): array
    {
        if (! static::isConfigured()) {
            return [];
        }

        return Cache::remember('filament_tasks:assignee_options', 300, function () {
            $model      = config('filament-tasks.assignee_model');
            $labelField = config('filament-tasks.assignee_label_field', 'name');
            $emailField = config('filament-tasks.assignee_email_field', 'email');
            $orderBy    = config('filament-tasks.assignee_order_by') ?? $labelField;

            return $model::query()
                ->orderBy($orderBy)
                ->get([$emailField, $labelField])
                ->mapWithKeys(fn ($record) => [
                    $record->{$emailField} => $record->{$labelField},
                ])
                ->toArray();
        });
    }

    /**
     * Returns the display label for a given email address.
     * Falls back to the email itself if not found.
     */
    public static function labelForEmail(?string $email): string
    {
        if (! $email) return '—';
        if (! static::isConfigured()) return $email;

        return static::options()[$email] ?? $email;
    }

    /**
     * Flush the assignee options cache (e.g. after the local model changes).
     */
    public static function flush(): void
    {
        Cache::forget('filament_tasks:assignee_options');
    }
}