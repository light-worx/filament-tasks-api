<?php

namespace Lightworx\FilamentTasks\Support;

use Lightworx\TasksApiClient\Facades\TasksApi;
use Lightworx\TasksApiClient\TasksApiClient;

/**
 * Resolves status and project data from the API.
 *
 * IMPORTANT: The API stores status as the label string (e.g. "Clarify"),
 * not the numeric id. So all option arrays are keyed by label.
 */
class StatusHelper
{
    /**
     * Options for Select inputs — keyed by label since that is what
     * the API stores on tasks (e.g. status: "Clarify").
     *
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
     * ['Clarify' => ['id' => 3, 'label' => 'Clarify', 'colour' => '#hex', ...]]
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

    /**
     * Hex badge colour for a status value (which is the label string).
     */
    public static function badgeColour(?string $value): string
    {
        if (!$value) return 'gray';
        return static::all()[$value]['colour'] ?? 'gray';
    }

    /**
     * Display label — since the value IS the label, just return it.
     */
    public static function label(?string $value): string
    {
        if (!$value) return '—';
        return $value;
    }

    /**
     * Project options for Select inputs.
     *
     * ProjectQuery::get() uses ->json('data') but the API returns a plain
     * array with no 'data' wrapper, so the SDK returns []. We bypass the SDK
     * here and call the HTTP client directly.
     *
     * ['2' => 'WMC', '1' => 'Home', ...]
     */
    public static function projectOptions(): array
    {
        try {
            $response = app(TasksApiClient::class)
                ->http()
                ->get('/api/projects')
                ->json();

            return collect($response)
                ->mapWithKeys(fn ($p) => [(string) $p['id'] => $p['name']])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Project name for a given project_id value.
     */
    public static function projectLabel(?string $id): string
    {
        if (!$id) return '—';
        return static::projectOptions()[(string) $id] ?? $id;
    }
}