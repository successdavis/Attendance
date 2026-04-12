<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\AttendanceSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AttendanceSettingsService $settings
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $this->settings->getAllGrouped(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->setMany(
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Settings saved successfully.');
    }
}
