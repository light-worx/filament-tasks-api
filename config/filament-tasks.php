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

     /*
    |--------------------------------------------------------------------------
    | Assignee Model
    |--------------------------------------------------------------------------
    | Optional. If set, the "Assigned To" field on the task form becomes a
    | searchable Select populated from this Eloquent model rather than a
    | plain email text input.
    |
    | assignee_model       - Fully-qualified model class name
    | assignee_label_field - The field shown as the option label in the dropdown
    | assignee_email_field - The field whose value is stored as assigned_email
    | assignee_order_by    - Field to order the dropdown by (default: label field)
    |
    | Example:
    |   'assignee_model'       => App\Models\Individual::class,
    |   'assignee_label_field' => 'name',
    |   'assignee_email_field' => 'email',
    |   'assignee_order_by'    => 'name',
    |
    | Or set via .env:
    |   TASKS_ASSIGNEE_MODEL=App\Models\Individual
    |   TASKS_ASSIGNEE_LABEL=name
    |   TASKS_ASSIGNEE_EMAIL=email
    */
    'assignee_model'       => env('TASKS_ASSIGNEE_MODEL', null),
    'assignee_label_field' => env('TASKS_ASSIGNEE_LABEL', 'name'),
    'assignee_email_field' => env('TASKS_ASSIGNEE_EMAIL', 'email'),
    'assignee_order_by'    => env('TASKS_ASSIGNEE_ORDER_BY', null), // defaults to label field
];