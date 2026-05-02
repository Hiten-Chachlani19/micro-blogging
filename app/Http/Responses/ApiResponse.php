<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse implements Responsable
{
    private function __construct(
        private readonly mixed $data,
        private readonly array $errors,
        private readonly array $meta,
        private readonly int $status,
    ) {}

    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return (new self($data, [], $meta, $status))->toResponse(request());
    }

    public static function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return (new self(null, array_merge([['field' => null, 'message' => $message]], $errors), [], $status))->toResponse(request());
    }

    public static function validationError(array $errors): JsonResponse
    {
        $formatted = [];
        foreach ($errors as $field => $messages) {
            foreach ((array) $messages as $message) {
                $formatted[] = ['field' => $field, 'message' => $message];
            }
        }

        return (new self(null, $formatted, [], 422))->toResponse(request());
    }

    public static function paginated(LengthAwarePaginator $paginator, callable $transformer = null): JsonResponse
    {
        $items = $transformer
            ? $paginator->getCollection()->map($transformer)->values()
            : $paginator->items();

        $meta = [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];

        return (new self($items, [], $meta, 200))->toResponse(request());
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse([
            'data'   => $this->data,
            'errors' => $this->errors,
            'meta'   => $this->meta,
        ], $this->status);
    }
}
