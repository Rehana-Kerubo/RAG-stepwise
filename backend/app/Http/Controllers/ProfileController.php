<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * The Profile tab for the logged-in user (agent or manager).
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return view('profile', [
            'user' => $user,
            'agent' => $user->agent,
        ]);
    }
}
