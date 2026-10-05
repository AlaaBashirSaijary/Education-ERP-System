<?php

use App\Http\Controllers\WebAuthController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

Route::get('lang/{locale}', [WebAuthController::class, 'lang'])->name('lang');

Route::middleware('guest')->group(function () {
    Route::get('login', [WebAuthController::class, 'show'])->name('login');
    Route::post('login', [WebAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [WebAuthController::class, 'logout'])->name('logout');
    Route::redirect('/', '/dashboard');

    Route::get('dashboard', Livewire\Dashboard::class)->name('dashboard');
    Route::get('students', Livewire\Students::class)->name('students');
    Route::get('students/{student}/report-card', Livewire\ReportCard::class)->name('report-card');
    Route::get('fees', Livewire\Fees::class)->name('fees')->middleware('role:admin,accountant,parent');
    Route::get('timetable', Livewire\Timetable::class)->name('timetable')->middleware('role:admin,teacher,parent');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('attendance', Livewire\Attendance::class)->name('attendance');
        Route::get('gate', Livewire\Gate::class)->name('gate');
        Route::get('grades', Livewire\Grades::class)->name('grades');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('messages', Livewire\Messages::class)->name('messages');
        Route::get('students/{student}/card', [\App\Http\Controllers\RosterController::class, 'card'])->name('student-card');
    });
});
