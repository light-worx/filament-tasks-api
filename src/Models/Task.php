<?php

namespace Lightworx\FilamentTasks\Models;

use Illuminate\Database\Eloquent\Model;
use Livewire\Wireable;
use Lightworx\TasksApiClient\DTO\TaskData;

class Task extends Model implements Wireable
{
    protected $fillable = [
        'id', 'title', 'description',
        'assigned_email', 'status', 'project_id', 'due_at',
    ];

    public $timestamps = false;

    public function getTable(): string { return 'migrations'; }
    public function getRouteKeyName(): string { return 'id'; }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        return (new static())->forceFill(['id' => (string) $value])
            ->tap(fn ($i) => $i->exists = true);
    }

    public function toLivewire(): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'assigned_email' => $this->assigned_email,
            'status'         => $this->status,
            'project_id'     => $this->project_id,
            'due_at'         => $this->due_at,
            'exists'         => $this->exists,
        ];
    }

    public static function fromLivewire($value): static
    {
        $instance = new static();
        $instance->forceFill([
            'id'             => $value['id'] ?? null,
            'title'          => $value['title'] ?? null,
            'description'    => $value['description'] ?? null,
            'assigned_email' => $value['assigned_email'] ?? null,
            'status'         => $value['status'] ?? null,
            'project_id'     => $value['project_id'] ?? null,
            'due_at'         => $value['due_at'] ?? null,
        ]);
        $instance->exists = $value['exists'] ?? true;
        return $instance;
    }

    public static function fromDto(TaskData $dto): static
    {
        $instance = new static();
        $instance->forceFill([
            'id'             => $dto->id,
            'title'          => $dto->title,
            'description'    => $dto->description,
            'assigned_email' => $dto->assigned_email,
            'status'         => $dto->status,
            'project_id'     => $dto->project_id,
            'due_at'         => $dto->due_at,
        ]);
        $instance->exists = true;
        return $instance;
    }
}