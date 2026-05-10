<?php

namespace Lightworx\FilamentTasks\Models;

use Illuminate\Database\Eloquent\Model;
use Livewire\Wireable;
use Lightworx\TasksApiClient\DTO\ProjectData;

class Project extends Model implements Wireable
{
    protected $fillable = [
        'id', 'name', 'description', 'status', 'created_at',
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
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'created_at'  => $this->created_at,
            'exists'      => $this->exists,
        ];
    }

    public static function fromLivewire($value): static
    {
        $instance = new static();
        $instance->forceFill([
            'id'          => $value['id'] ?? null,
            'name'        => $value['name'] ?? null,
            'description' => $value['description'] ?? null,
            'status'      => $value['status'] ?? null,
            'created_at'  => $value['created_at'] ?? null,
        ]);
        $instance->exists = $value['exists'] ?? true;
        return $instance;
    }

    public static function fromDto(ProjectData $dto): static
    {
        $instance = new static();
        $instance->forceFill([
            'id'          => $dto->id,
            'name'        => $dto->name,
            'description' => $dto->description,
            'status'      => $dto->status,
            'created_at'  => $dto->created_at,
        ]);
        $instance->exists = true;
        return $instance;
    }
}