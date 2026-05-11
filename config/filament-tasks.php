<?php

return [
    'base_url' => env('TASKS_API_URL', 'http://localhost:8000'),

    'client_id'     => env('TASKS_API_CLIENT_ID', ''),
    'client_secret' => env('TASKS_API_CLIENT_SECRET', ''),

    'navigation_group' => env('TASKS_NAV_GROUP', 'Task Management'),
    'navigation_sort'  => 10,

    /*
    |--------------------------------------------------------------------------
    | Assigned Email Scope
    |--------------------------------------------------------------------------
    |
    | If your API client does NOT have can_view_all_tasks permission, all task
    | queries must include an assigned_email or the API returns nothing.
    |
    | Set TASKS_API_ASSIGNED_EMAIL in your .env to scope all plugin queries
    | to a specific assignee. Leave empty if your client has can_view_all_tasks.
    |
    | Example: TASKS_API_ASSIGNED_EMAIL=person@example.com
    |
    */
    'assigned_email' => env('TASKS_API_ASSIGNED_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | Owner Email (private project visibility)
    |--------------------------------------------------------------------------
    |
    | Set TASKS_API_OWNER_EMAIL to unlock visibility of private projects owned
    | by that email address (requires can_lookup_assigned_tasks on the client).
    |
    | Example: TASKS_API_OWNER_EMAIL=owner@example.com
    |
    */
    'owner_email' => env('TASKS_API_OWNER_EMAIL', ''),
];