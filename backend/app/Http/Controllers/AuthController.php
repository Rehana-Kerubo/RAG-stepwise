<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * The login page. The email-and-password form only shows on local.
     */
    public function showLogin()
    {
        return view('welcome', ['passwordLogin' => app()->isLocal()]);
    }

    /**
     * Email-and-password login for the seeded demo accounts (local only,
     * the route is not registered anywhere else).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'Wrong email or password.'])
                ->onlyInput('email');
        }

        return $this->finishLogin($request, Auth::user());
    }

    /**
     * Send the user to Google's sign-in page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends the user back here. Only emails already in `users` may sign in.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        $email = strtolower((string) $googleUser->getEmail());

        if (! ($googleUser->user['email_verified'] ?? false)) {
            return redirect('/')->withErrors(['email' => 'Your Google email address is not verified.']);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect('/')->withErrors([
                'email' => "This Google account ($email) is not registered in StepWise. Ask your manager to add you.",
            ]);
        }

        // Keep the Google profile photo up to date for the Profile tab.
        $user->avatar = $googleUser->getAvatar();
        $user->save();

        Auth::login($user);

        return $this->finishLogin($request, $user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Shared last step of both logins: refuse agent accounts that are not
     * linked to an agent, then send the user to their own home page.
     */
    private function finishLogin(Request $request, User $user)
    {
        if (! $user->isManager() && ! $user->agent_id) {
            Auth::logout();

            return redirect('/')->withErrors([
                'email' => 'Your account is not linked to an agent yet. Ask your manager.',
            ]);
        }

        // New session id after login, so an old one cannot be reused.
        $request->session()->regenerate();

        // Always go to the user's own home. (Not intended(): the service worker
        // requests /chat in the background, which would send managers there.)
        $request->session()->forget('url.intended');

        return redirect($user->homeUrl());
    }
}
