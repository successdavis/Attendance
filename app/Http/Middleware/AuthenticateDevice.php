<?php

namespace App\Http\Middleware;

use App\Models\AttendanceDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    /**
     * Authenticate an attendance device by its Bearer API token.
     *
     * The plain token is hashed with SHA-256 and compared against
     * attendance_devices.api_token_hash. If valid, the resolved
     * AttendanceDevice model is injected into the request so downstream
     * controllers and services can reference it without another DB query.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Device token required.'], 401);
        }

        $hashed = hash('sha256', $token);

        $device = AttendanceDevice::where('api_token_hash', $hashed)->first();

        if (! $device) {
            return response()->json(['message' => 'Invalid device token.'], 401);
        }

        if (! $device->isActive()) {
            return response()->json(['message' => 'Device is not active.'], 403);
        }

        // Update heartbeat info — fire-and-forget, failure is non-critical
        $device->updateQuietly([
            'last_seen_at' => now(),
            'last_ip'      => $request->ip(),
        ]);

        // Inject device into request so it's accessible via $request->device
        $request->merge(['device' => $device]);
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
