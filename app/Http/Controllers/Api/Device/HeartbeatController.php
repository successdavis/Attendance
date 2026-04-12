<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'ok'          => true,
            'server_time' => now()->toDateTimeString(),
        ]);
    }
}
