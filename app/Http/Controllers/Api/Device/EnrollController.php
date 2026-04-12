<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollController extends Controller
{
    /**
     * Enroll an RFID card for a user via a device terminal.
     * Full implementation in Phase 4.
     */
    public function rfid(Request $request): JsonResponse
    {
        return response()->json(['message' => 'RFID enrollment — implementation pending.'], 501);
    }

    public function fingerprint(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Fingerprint enrollment — implementation pending.'], 501);
    }

    public function face(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Face enrollment — implementation pending.'], 501);
    }
}
