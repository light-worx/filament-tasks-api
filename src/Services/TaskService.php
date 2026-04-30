<?php

namespace Lightworx\FilamentTasksApi\Services;

use Lightworx\TasksApiClient\Facades\TasksApi;

class TaskService
{
    public function paginate(array $filters = [])
    {
        $query = TasksApi::tasks();

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['project_id'])) {
            $query->project($filters['project_id']);
        }

        if (!empty($filters['assigned_email'])) {
            $query->assignedTo($filters['assigned_email']);
        }

        return $query->paginate(50);
    }

    public function create(array $data)
    {
        return TasksApi::tasks()->create($data);
    }

    public function update(string $id, array $data)
    {
        return TasksApi::tasks()->update($id, $data);
    }

    public function delete(string $id)
    {
        return TasksApi::tasks()->delete($id);
    }
}