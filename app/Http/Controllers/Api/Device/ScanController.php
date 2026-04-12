<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    /**
     * Process an attendance scan submitted by a physical device.
     * Full implementation in Phase 4.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Phase 4 implementation placeholder
        return response()->json(['message' => 'Scan endpoint — implementation pending.'], 501);
    }
}
