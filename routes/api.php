<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    // Everyone (parents are scoped to their own children inside the controllers)
    Route::get('students', [RosterController::class, 'students']);
    Route::get('students/{student}/report-card', [GradeController::class, 'reportCard']);
    Route::get('students/{student}/fees', [FeeController::class, 'statement']);
    Route::get('classes/{class}/timetable', [TimetableController::class, 'forClass']);

    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('attendance/scan', [AttendanceController::class, 'scan']);
        Route::post('attendance/bulk', [AttendanceController::class, 'bulk']);
        Route::get('attendance', [AttendanceController::class, 'report']);
        Route::post('exams', [GradeController::class, 'storeExam']);
        Route::post('exams/{exam}/marks', [GradeController::class, 'storeMarks']);
        Route::post('students/{student}/report-card/send', [GradeController::class, 'sendReportCard']);
        Route::get('teachers/{teacher}/timetable', [TimetableController::class, 'forTeacher']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('classes', [RosterController::class, 'storeClass']);
        Route::post('subjects', [RosterController::class, 'storeSubject']);
        Route::post('students', [RosterController::class, 'storeStudent']);
        Route::get('students/{student}/qr', [RosterController::class, 'qr']);
        Route::post('timetable', [TimetableController::class, 'store']);
    });

    Route::middleware('role:admin,accountant')->group(function () {
        Route::post('students/{student}/fee-plan', [FeeController::class, 'createPlan']);
        Route::post('fees/{fee}/payments', [FeeController::class, 'pay']);
        Route::get('fees/overdue', [FeeController::class, 'overdue']);
    });
});
