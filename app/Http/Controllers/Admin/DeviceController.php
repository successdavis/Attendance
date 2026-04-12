<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceDevice;
use App\Services\DeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function __construct(private readonly DeviceService $deviceService) {}

    public function index(): Response
    {
        $devices = AttendanceDevice::with('registeredBy')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Devices/Index', compact('devices'));
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Devices/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'device_uid' => 'required|string|max:100|unique:attendance_devices,device_uid',
            'type'      => 'required|in:rfid_reader,fingerprint_scanner,face_camera,multi',
            'location'  => 'nullable|string|max:200',
            'branch'    => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
        ]);

        ['device' => $device, 'plainToken' => $plainToken] = $this->deviceService->register(
            $data,
            $request->user()->id
        );

        // Show token once via flash — it's never retrievable again
        return redirect()->route('admin.devices.index')
            ->with('newDeviceToken', $plainToken)
            ->with('newDeviceName', $device->name)
            ->with('success', "Device \"{$device->name}\" registered. Save the API token shown below.");
    }

    public function edit(AttendanceDevice $device): Response
    {
        return Inertia::render('Admin/Devices/Edit', compact('device'));
    }

    public function update(Request $request, AttendanceDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'name'       => 'sometimes|string|max:150',
            'type'       => 'sometimes|in:rfid_reader,fingerprint_scanner,face_camera,multi',
            'location'   => 'sometimes|nullable|string|max:200',
            'branch'     => 'sometimes|nullable|string|max:100',
            'ip_address' => 'sometimes|nullable|ip',
            'status'     => 'sometimes|in:active,inactive,maintenance',
        ]);

        $device->update($data);

        return back()->with('success', 'Device updated.');
    }

    public function destroy(AttendanceDevice $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('admin.devices.index')->with('success', 'Device removed.');
    }

    public function rotateToken(Request $request, AttendanceDevice $device): RedirectResponse
    {
        $plainToken = $this->deviceService->rotateToken($device, $request->user()->id);

        return back()
            ->with('newDeviceToken', $plainToken)
            ->with('newDeviceName', $device->name)
            ->with('success', 'Token rotated. Save the new token — it will not be shown again.');
    }
}
