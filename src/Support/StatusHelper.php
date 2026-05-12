<?php

namespace Lightworx\FilamentTasks\Support;

use Lightworx\TasksApiClient\Facades\TasksApi;
use Lightworx\TasksApiClient\TasksApiClient;

class StatusHelper
{
    /**
     * Options keyed by label — the API stores label as the status value.
     * ['Clarify' => 'Clarify', 'Done' => 'Done', ...]
     */
    public static function options(): array
    {
        try {
            return collect(TasksApi::meta()->statuses())
                ->sortBy('sort_order')
                ->mapWithKeys(fn ($s) => [$s['label'] => $s['label']])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Full status objects indexed by label.
     */
    public static function all(): array
    {
        try {
            return collect(TasksApi::meta()->statuses())
                ->keyBy('label')
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function badgeColour(?string $value): string
    {
        if (!$value) return 'gray';
        return static::all()[$value]['colour'] ?? 'gray';
    }

    public static function label(?string $value): string
    {
        if (!$value) return '—';
        return $value;
    }

    /**
     * The label of the "done" status — searches for "done" or "complet",
     * falls back to the last status by sort_order.
     */
    public static function doneStatusLabel(): ?string
    {
        $statuses = collect(static::all())
            ->filter(fn ($s) => ($s['is_active'] ?? true))
            ->sortBy('sort_order');

        $match = $statuses->first(
            fn ($s) => str_contains(strtolower($s['label']), 'done')
                || str_contains(strtolower($s['label']), 'complet')
        );

        if ($match) {
            return $match['label'];
        }

        return $statuses->last()['label'] ?? null;
    }

    /**
     * Project options for Select inputs.
     */
    public static function projectOptions(): array
    {
        try {
            return collect(TasksApi::projects()->get())
                ->mapWithKeys(fn ($dto) => [(string) $dto->id => $dto->name])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Project name for a given project_id.
     */
    public static function projectLabel(?string $id): string
    {
        if (!$id) return '—';
        return static::projectOptions()[(string) $id] ?? $id;
    }
}