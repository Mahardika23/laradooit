<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Report whether the instance can serve requests.
     *
     * A reverse proxy or deploy script polls this, so it must never require
     * authentication and must fail when the database is unreachable.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->select('select 1');
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'database' => 'unreachable',
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'database' => 'ok',
        ]);
    }
}
