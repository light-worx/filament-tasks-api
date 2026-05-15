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

    /**
     * Hydrate from a TaskData DTO.
     */
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

    /**
     * Hydrate from a plain array (used when cache returns arrays not DTOs).
     */
    /**
     * Hydrate from either a TaskData DTO or a plain array.
     * Used by ListTasks which may receive either depending on cache state.
     */
    public static function fromItem(mixed $item): static
    {
        if ($item instanceof \Lightworx\TasksApiClient\DTO\TaskData) {
            return static::fromDto($item);
        }
        return static::fromArray((array) $item);
    }

    public static function fromArray(array $data): static
    {
        $instance = new static();
        $instance->forceFill([
            'id'             => $data['id'] ?? null,
            'title'          => $data['title'] ?? null,
            'description'    => $data['description'] ?? null,
            'assigned_email' => $data['assigned_email'] ?? null,
            'status'         => $data['status'] ?? null,
            'project_id'     => $data['project_id'] ?? null,
            'due_at'         => $data['due_at'] ?? null,
        ]);
        $instance->exists = true;
        return $instance;
    }
}