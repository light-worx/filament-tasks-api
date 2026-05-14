<?php

namespace Lightworx\FilamentTasks\Support;

use Illuminate\Support\Facades\Cache;
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\TasksApiClient\Facades\TasksApi;

class TaskCache
{
    protected static int $ttl = 300; // 5 minutes

    // ──────────────────────────────────────────────
    // Version
    // ──────────────────────────────────────────────

    protected static function version(): int
    {
        if (! Cache::has('filament_tasks:version')) {
            Cache::put('filament_tasks:version', 0);
        }
        return (int) Cache::get('filament_tasks:version', 0);
    }

    public static function flush(): void
    {
        Cache::increment('filament_tasks:version');
    }

    // ──────────────────────────────────────────────
    // Cache keys
    // ──────────────────────────────────────────────

    public static function tasksKey(int $page, int $perPage, ?string $status): string
    {
        $plugin = FilamentTasksPlugin::get();
        $v      = static::version();

        return implode(':', array_filter([
            'filament_tasks',
            "v{$v}",
            $plugin->getAssignedEmail() ?? 'all',
            $status ?? 'any',
            "p{$page}",
            "pp{$perPage}",
        ]));
    }

    public static function statsKey(): string
    {
        $plugin = FilamentTasksPlugin::get();
        $v      = static::version();

        return "filament_tasks:v{$v}:stats:" . ($plugin->getAssignedEmail() ?? 'all');
    }

    // ──────────────────────────────────────────────
    // Fetchers — store plain arrays, never PHP objects
    // ──────────────────────────────────────────────

    public static function tasks(int $page, int $perPage, ?string $status = null): array
    {
        return Cache::remember(
            static::tasksKey($page, $perPage, $status),
            static::$ttl,
            fn () => static::fetchTasks($page, $perPage, $status)
        );
    }

    public static function stats(): array
    {
        return Cache::remember(
            static::statsKey(),
            static::$ttl,
            fn () => static::fetchStats()
        );
    }

    // ──────────────────────────────────────────────
    // Force refresh
    // ──────────────────────────────────────────────

    public static function refreshTasks(int $page, int $perPage, ?string $status = null): array
    {
        $key   = static::tasksKey($page, $perPage, $status);
        $fresh = static::fetchTasks($page, $perPage, $status);
        Cache::put($key, $fresh, static::$ttl);
        return $fresh;
    }

    public static function refreshStats(): array
    {
        $key   = static::statsKey();
        $fresh = static::fetchStats();
        Cache::put($key, $fresh, static::$ttl);
        return $fresh;
    }

    // ──────────────────────────────────────────────
    // Internal fetchers — convert DTOs to plain arrays before caching
    // ──────────────────────────────────────────────

    protected static function fetchTasks(int $page, int $perPage, ?string $status): array
    {
        $plugin = FilamentTasksPlugin::get();
        $query  = TasksApi::tasks()->latest();

        if ($assignedEmail = $plugin->getAssignedEmail()) {
            $query->assignedTo($assignedEmail);
        }
        if ($ownerEmail = $plugin->getOwnerEmail()) {
            $query->ownerEmail($ownerEmail);
        }
        if ($status) {
            $query->whereStatus($status);
        }

        $result = $query->paginate($perPage);

        // Convert TaskData DTOs to plain arrays so the cache stores only
        // serializable primitives — no __PHP_Incomplete_Class on retrieval.
        return [
            'data' => array_map(
                fn ($dto) => [
                    'id'             => $dto->id,
                    'title'          => $dto->title,
                    'description'    => $dto->description,
                    'assigned_email' => $dto->assigned_email,
                    'status'         => $dto->status,
                    'project_id'     => $dto->project_id,
                    'due_at'         => $dto->due_at,
                ],
                $result['data']
            ),
            'meta' => $result['meta'],
        ];
    }

    protected static function fetchStats(): array
    {
        $plugin = FilamentTasksPlugin::get();
        $query  = TasksApi::tasks();

        if ($assignedEmail = $plugin->getAssignedEmail()) {
            $query->assignedTo($assignedEmail);
        }
        if ($ownerEmail = $plugin->getOwnerEmail()) {
            $query->ownerEmail($ownerEmail);
        }

        // Convert DTOs to plain arrays
        return array_map(
            fn ($dto) => [
                'id'             => $dto->id,
                'title'          => $dto->title,
                'description'    => $dto->description,
                'assigned_email' => $dto->assigned_email,
                'status'         => $dto->status,
                'project_id'     => $dto->project_id,
                'due_at'         => $dto->due_at,
            ],
            $query->get()
        );
    }
}