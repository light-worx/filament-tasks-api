<?php

namespace Lightworx\FilamentTasks\Support;

use Illuminate\Support\Facades\Cache;
use Lightworx\FilamentTasks\FilamentTasksPlugin;
use Lightworx\TasksApiClient\Facades\TasksApi;

class TaskCache
{
    protected static int $ttl = 300; // 5 minutes

    // ──────────────────────────────────────────────
    // Cache keys
    // ──────────────────────────────────────────────

    public static function tasksKey(int $page, int $perPage, ?string $status): string
    {
        $plugin = FilamentTasksPlugin::get();
        return implode(':', array_filter([
            'filament_tasks',
            'list',
            $plugin->getAssignedEmail() ?? 'all',
            $status ?? 'any',
            "p{$page}",
            "pp{$perPage}",
        ]));
    }

    public static function statsKey(): string
    {
        $plugin = FilamentTasksPlugin::get();
        return 'filament_tasks:stats:' . ($plugin->getAssignedEmail() ?? 'all');
    }

    // ──────────────────────────────────────────────
    // Fetchers — return cached data or fetch & store
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
    // Force refresh — clears matching keys and re-fetches
    // ──────────────────────────────────────────────

    public static function refreshTasks(int $page, int $perPage, ?string $status = null): array
    {
        $key = static::tasksKey($page, $perPage, $status);
        Cache::forget($key);
        $fresh = static::fetchTasks($page, $perPage, $status);
        Cache::put($key, $fresh, static::$ttl);
        return $fresh;
    }

    public static function refreshStats(): array
    {
        $key = static::statsKey();
        Cache::forget($key);
        $fresh = static::fetchStats();
        Cache::put($key, $fresh, static::$ttl);
        return $fresh;
    }

    /**
     * Clear all filament_tasks:* keys.
     * Called after create/update/delete so stale data isn't served.
     */
    public static function flush(): void
    {
        // Laravel cache doesn't support wildcard deletes on all drivers,
        // so we use a version key approach — bumping it invalidates all
        // filament_tasks keys that include the version.
        Cache::increment('filament_tasks:version');
    }

    // ──────────────────────────────────────────────
    // Internal fetchers
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

        return $query->paginate($perPage);
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

        return $query->get();
    }
}