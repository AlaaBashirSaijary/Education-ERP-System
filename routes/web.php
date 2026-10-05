<?php

use App\Http\Controllers\WebAuthController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

// PWA: public, because the browser fetches them before anyone signs in.
Route::get('manifest.webmanifest', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('manifest');
Route::view('offline', 'offline')->name('offline');

Route::get('lang/{locale}', [WebAuthController::class, 'lang'])->name('lang');

// Landing page (demo mode) or straight to the app.
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard')
        : (config('school.demo_mode') ? view('landing') : redirect()->route('login'));
})->name('home');

// Demo mode only: one-click sign-in (the controller 404s otherwise).
Route::post('demo/login/{role}', [\App\Http\Controllers\DemoController::class, 'login'])->middleware('throttle:30,1')->name('demo.login');

Route::middleware('guest')->group(function () {
    Route::get('login', [WebAuthController::class, 'show'])->name('login');
    Route::post('login', [WebAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');

    Route::get('register', [\App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
    Route::post('register', [\App\Http\Controllers\Auth\RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');

    Route::get('forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'form'])->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [WebAuthController::class, 'logout'])->name('logout');

    // Which academic year the pages show; stored in the session, defaults to the current year.
    Route::post('year/{year}', function (\Illuminate\Http\Request $request, \App\Models\AcademicYear $year) {
        $request->session()->put('year_id', $year->id);

        return back();
    })->name('year.switch');

    Route::get('dashboard', Livewire\Dashboard::class)->name('dashboard');
    Route::get('students', Livewire\Students::class)->name('students');
    Route::get('students/{student}', Livewire\StudentProfile::class)->whereNumber('student')->name('student-profile');
    Route::get('students/{student}/report-card', Livewire\ReportCard::class)->name('report-card');
    Route::get('fees', Livewire\Fees::class)->name('fees')->middleware('role:admin,accountant,parent');
    Route::get('timetable', Livewire\Timetable::class)->name('timetable')->middleware('role:admin,teacher,parent');

    Route::get('profile', Livewire\Profile::class)->name('profile');
    Route::get('payments/{payment}/receipt', [\App\Http\Controllers\ReportController::class, 'receipt'])->name('receipt');

    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('reports/finance', Livewire\FinanceReport::class)->name('finance-report');
        Route::get('reports/finance.csv', [\App\Http\Controllers\ReportController::class, 'financeCsv'])->name('finance-csv');
    });

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('reports/attendance', Livewire\AttendanceReport::class)->name('attendance-report');
        Route::get('reports/attendance.csv', [\App\Http\Controllers\ReportController::class, 'attendanceCsv'])->name('attendance-csv');
        Route::get('announcements', Livewire\Announcements::class)->name('announcements');
        Route::get('attendance', Livewire\Attendance::class)->name('attendance');
        Route::get('gate', Livewire\Gate::class)->name('gate');
        Route::get('grades', Livewire\Grades::class)->name('grades');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('messages', Livewire\Messages::class)->name('messages');
        Route::get('users', Livewire\Users::class)->name('users');
        Route::get('setup', Livewire\Setup::class)->name('setup');
        Route::get('years', Livewire\Years::class)->name('years');
        Route::get('staff-attendance', Livewire\StaffAttendance::class)->name('staff-attendance');
        Route::get('reports/staff-attendance.csv', [\App\Http\Controllers\ReportController::class, 'staffCsv'])->name('staff-csv');
        Route::get('students/{student}/card', [\App\Http\Controllers\RosterController::class, 'card'])->name('student-card');
    });
});
