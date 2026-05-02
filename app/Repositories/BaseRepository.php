<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function findById(int $id): mixed
    {
        return $this->model->findOrFail($id);
    }

    public function findAll(): mixed
    {
        return $this->model->all();
    }

    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): mixed
    {
        $record = $this->findById($id);
        $record->update($data);

        return $record;
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }
}
