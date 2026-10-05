<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GuardianLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Self-service sign-up for parents only. Staff accounts are created by an admin. */
class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request, GuardianLinkService $links)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:100|confirmed',
            'phone' => 'required|string|min:8|max:20',
            'student_no' => 'required|string|max:30',
        ]);

        // One generic message for every mismatch, so the form cannot be used to discover which students exist.
        $fail = fn () => throw ValidationException::withMessages(['student_no' => __('We could not match these details to a student. Check the student number and the phone number the school has on file, or contact the school.')]);

        if (! $links->canClaim($data['student_no'], $data['phone'])) {
            $fail();
        }

        $user = DB::transaction(function () use ($data, $links, $fail) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'password' => $data['password'], 'role' => 'parent',
            ]);
            if (! $links->claim($user, $data['student_no'], $data['phone'])) {
                $fail(); // lost a race with another sign-up; rolls the account back
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('ok', __('Welcome! Your account is ready and your child is linked.'));
    }
}
