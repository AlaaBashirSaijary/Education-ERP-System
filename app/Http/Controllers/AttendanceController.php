<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /** Gate scanner: a QR token or fingerprint id marks the student present (or late). */
    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_token' => 'required_without:fingerprint_id|string',
            'fingerprint_id' => 'required_without:qr_token|string',
        ]);

        $result = app(AttendanceService::class)->scan($data['qr_token'] ?? null, $data['fingerprint_id'] ?? null, $request->user());
        abort_unless($result, 404, 'Unknown student.');

        return response()->json(['student' => $result['student']->name,
            'status' => $result['attendance']->status, 'duplicate' => $result['duplicate']]);
    }

    /** Teacher records a whole class manually: [{student_id, status}, ...]. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late',
        ]);

        $service = app(AttendanceService::class);

        foreach ($data['records'] as $r) {
            $service->mark($r['student_id'], $data['date'], $r['status'], $request->user());
        }

        return response()->noContent();
    }

    public function report(Request $request)
    {
        $data = $request->validate(['date' => 'required|date', 'school_class_id' => 'nullable|exists:school_classes,id']);

        return Attendance::with('student:id,name,school_class_id')
            ->whereDate('date', $data['date'])
            ->when($data['school_class_id'] ?? null,
                fn ($q, $c) => $q->whereHas('student', fn ($s) => $s->where('school_class_id', $c)))
            ->get();
    }
}
