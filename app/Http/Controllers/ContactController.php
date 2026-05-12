<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show()
    {
        return Inertia::render('Contact');
    }

    public function send(Request $request)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:150'],
            'email'   => ['required', 'email', 'max:200'],
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        // TODO: dispatch a mail notification or log to a support table.
        // For now we simply redirect back with a success flash.

        return redirect()->route('contact')->with('success', true);
    }
}
