<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'device'      => $request->device?->only(['name', 'location', 'status']),
            'server_time' => now()->toDateTimeString(),
        ]);
    }
}
