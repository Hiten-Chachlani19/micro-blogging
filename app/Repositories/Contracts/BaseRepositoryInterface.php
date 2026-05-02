<?php

namespace App\Repositories\Contracts;

interface BaseRepositoryInterface
{
    public function findById(int $id): mixed;

    public function findAll(): mixed;

    public function create(array $data): mixed;

    public function update(int $id, array $data): mixed;

    public function delete(int $id): bool;
}
