<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::with('causer', 'subject')
            ->orderByDesc('created_at');

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->query('causer_id'))
                  ->where('causer_type', \App\Models\User::class);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->query('subject_type'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $activities = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/Audit/Index', compact('activities'));
    }
}
