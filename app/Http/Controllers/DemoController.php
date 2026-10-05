<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** One-click sign-in to the seeded demo accounts. Exists only in demo mode. */
class DemoController extends Controller
{
    public const ACCOUNTS = [
        'admin' => 'admin@school.test',
        'teacher' => 'teacher@school.test',
        'accountant' => 'accountant@school.test',
        'parent' => 'parent@school.test',
    ];

    public function login(Request $request, string $role)
    {
        abort_unless(config('school.demo_mode') && isset(self::ACCOUNTS[$role]), 404);

        $user = User::where('email', self::ACCOUNTS[$role])->first();
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => __('The demo is being prepared. Try again in a minute.')]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
