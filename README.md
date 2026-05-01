# Filament Tasks API Plugin

A **Filament 5** plugin that provides a full-featured Tasks management UI backed by the
[`light-worx/tasks-api-client`](https://github.com/light-worx/tasks-api-client) SDK.
All CRUD operations talk directly to the remote Tasks API — no local database tables required.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.2 |
| Laravel | ^11 \| ^12 \| ^13 |
| Filament | ^5.0 |
| light-worx/tasks-api-client | * |

---

## Installation

### 1. Require the package

```bash
composer require light-worx/filament-tasks-api
```

Because the SDK (`light-worx/tasks-api-client`) is not yet published to Packagist
you may need to add both repos as VCS sources in your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/light-worx/tasks-api-client"
    },
    {
        "type": "vcs",
        "url": "https://github.com/light-worx/filament-tasks-api"
    }
]
```

### 2. Publish & configure

```bash
php artisan vendor:publish --tag=filament-tasks-api-config
```

Add the following to your `.env`:

```env
TASKS_API_URL=https://your-tasks-api.example.com/api
TASKS_API_TOKEN=your-api-token-here

# Optional
TASKS_NAV_GROUP="Task Management"
```

---

## Registration

Register the plugin inside your **Filament Panel Provider** (e.g. `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Lightworx\FilamentTasksApi\FilamentTasksApiPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... your other panel config
        ->plugins([
            FilamentTasksApiPlugin::make()
                // optional — override the sidebar group label
                ->navigationGroup('My Tasks')
                // optional — show summary stats on the dashboard
                ->withStatsWidget(),
        ]);
}
```

That's it. The **Tasks** resource will appear in the sidebar under the configured group.

---

## Features

### Tasks Resource (full CRUD)

| Feature | Details |
|---|---|
| **List** | Paginated table, server-side search & filters |
| **Create** | Form with title, description, status, priority, due date, assigned-to |
| **Edit** | Pre-populated form, changes PUT back to the API |
| **Delete** | Single row or bulk delete with confirmation |
| **Quick Complete** | One-click "Complete" action on each row |
| **Status badges** | Colour-coded: Pending (yellow), In Progress (blue), Completed (green), Cancelled (red) |
| **Priority badges** | Colour-coded: Low (green), Medium (yellow), High (red) |
| **Auto-refresh** | Table polls every 30 seconds |

### Dashboard Widget (optional)

Enable `->withStatsWidget()` to add a stats overview to the panel dashboard:

- Pending count
- In Progress count
- Completed count
- Cancelled count

---

## How it works

The plugin does **not** use Eloquent or any local database. Instead:

1. `ListTasks::getTableRecords()` calls `TasksClient::index()` and hydrates
   lightweight `Task` model instances (value objects) from the API response.
2. `CreateTask::handleRecordCreation()` calls `TasksClient::create()`.
3. `EditTask::resolveRecord()` calls `TasksClient::show()`.
4. `EditTask::handleRecordUpdate()` calls `TasksClient::update()`.
5. Delete actions call `TasksClient::delete()`.

All SDK calls respect the `base_url` and `token` you set in `config/filament-tasks-api.php`
(or `.env`).

---

## Configuration reference

```php
// config/filament-tasks-api.php

return [
    'base_url'         => env('TASKS_API_URL', 'http://localhost:8000/api'),
    'token'            => env('TASKS_API_TOKEN', ''),
    'navigation_group' => env('TASKS_NAV_GROUP', 'Task Management'),
    'navigation_sort'  => 10,
];
```

---

## SDK contract expected

The plugin assumes the `TasksClient` class (resolved from the container) exposes these methods:

| Method | Signature |
|---|---|
| `index` | `index(array $params = []): array` |
| `show` | `show(int\|string $id): array` |
| `create` | `create(array $data): array` |
| `update` | `update(int\|string $id, array $data): array` |
| `delete` | `delete(int\|string $id): void` |
| `stats` | `stats(): array` *(optional — used by the widget)* |

Responses may be wrapped in `['data' => [...], 'meta' => [...]]` (paginated)
or returned as a plain array — both formats are handled automatically.

---

## Extending

### Custom navigation

```php
FilamentTasksApiPlugin::make()
    ->navigationGroup('Operations')
    ->navigationSort(5)
```

### Override a page

```php
// app/Filament/Resources/TaskResource/Pages/ListTasks.php
use Lightworx\FilamentTasksApi\Resources\TaskResource\Pages\ListTasks as BaseListTasks;

class ListTasks extends BaseListTasks
{
    // add custom header widgets, actions, etc.
}
```

---

## License

MIT
