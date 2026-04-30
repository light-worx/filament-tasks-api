<?php

namespace Lightworx\FilamentTasksApi\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Lightworx\FilamentTasksApi\Resources\TaskResource;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;
}