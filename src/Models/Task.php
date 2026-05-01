<?php

namespace Lightworx\FilamentTasksApi\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A non-persisted Eloquent model used as a value-object so that Filament
 * resources, tables and forms can work with Task data fetched from the
 * remote API via the tasks-api-client SDK without touching a local DB.
 *
 * @property int|null    $id
 * @property string      $title
 * @property string      $description
 * @property string      $status         pending|in_progress|completed|cancelled
 * @property string      $priority       low|medium|high
 * @property string|null $due_date
 * @property string|null $assigned_to
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Task extends Model
{
    protected $fillable = [
        'id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assigned_to',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'due_date'   => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Point at the migrations table — it always exists in every Laravel app.
     * This lets Filament construct a valid Eloquent Builder during page setup
     * without a "table not found" error. Actual data always comes from the
     * API via getTableRecords(), never from this table.
     */
    public function getTable(): string
    {
        return 'migrations';
    }

    /**
     * The route key used by Filament to build edit/view URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Resolve for route model binding without hitting the DB.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        return (new static())->forceFill(['id' => $value]);
    }
}