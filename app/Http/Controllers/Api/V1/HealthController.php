<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $db = $this->check(fn () => DB::select('SELECT 1'));

        $allUp = $db === 'up';

        return ApiResponse::success(
            data: [
                'status'   => $allUp ? 'ok' : 'degraded',
                'services' => ['db' => $db],
            ],
            meta: [
                'time'    => now()->toIso8601String(),
                'version' => app()->version(),
            ],
            status: $allUp ? 200 : 503,
        );
    }

    private function check(callable $fn): string
    {
        try {
            $fn();

            return 'up';
        } catch (Throwable) {
            return 'down';
        }
    }
}
