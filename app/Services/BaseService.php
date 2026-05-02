<?php

namespace App\Services;

use App\Repositories\Contracts\BaseRepositoryInterface;

abstract class BaseService
{
    public function __construct(protected BaseRepositoryInterface $repository) {}
}
